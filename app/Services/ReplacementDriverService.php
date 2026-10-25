<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverVacancy;
use App\Models\TimeKeeping;
use App\Models\Vehicle;
use App\Models\VehicleDutyDay;
use App\Models\ViolationLog;
use App\Notifications\LicenceExpiredRelease;
use App\Notifications\ReplacementAssigned;
use App\Notifications\VehicleVacancyFilled;
use App\Notifications\VehicleVacancyOpen;
use App\Support\Audit;
use App\Support\Notifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Keeps vehicles driven. When a driver leaves a vehicle because they were suspended or their licence
 * expired, the seat becomes a {@see DriverVacancy}; this service fills it with the best free driver,
 * rebuilds the duty roster when the vehicle is shared again, and reports to the Maintenance Managers
 * whatever it could not fill.
 */
class ReplacementDriverService
{
    /**
     * Drivers who could take a vehicle today, best first: approved, not suspended, an unexpired licence,
     * no vehicle of their own and no inactivity warning outstanding. Ranked by a valid (rather than
     * soon-to-expire) licence, then fewest recent violations, then least recent overtime.
     *
     * @return Collection<int, Driver>
     */
    public function candidates(): Collection
    {
        $drivers = Driver::where('is_approved', true)
            ->where('is_rejected', false)
            ->where('is_suspended', false)
            ->whereNull('inactivity_warned_at')
            ->whereDoesntHave('vehicle')
            ->get()
            ->filter(fn (Driver $d) => in_array($d->licenseState(), ['valid', 'expiring'], true))
            ->values();

        if ($drivers->isEmpty()) {
            return $drivers;
        }

        $since = today()->subDays((int) config('fleet.ranking_days', 90))->toDateString();

        $violations = ViolationLog::whereIn('user_id', $drivers->pluck('user_id'))
            ->where('date_of_violation', '>=', $since)
            ->selectRaw('user_id, COUNT(*) as total')->groupBy('user_id')->pluck('total', 'user_id');

        $overtime = TimeKeeping::whereIn('driver_id', $drivers->pluck('id'))
            ->where('date', '>=', $since)
            ->selectRaw('driver_id, COALESCE(SUM(overtime_hours), 0) as total')->groupBy('driver_id')->pluck('total', 'driver_id');

        return $drivers->sortBy(fn (Driver $d) => [
            $d->licenseState() === 'expiring' ? 1 : 0,
            (int) ($violations[$d->user_id] ?? 0),
            (float) ($overtime[$d->id] ?? 0),
            $d->id,
        ])->values();
    }

    /**
     * A driver just left $vehicle: open a vacancy for the seat and, with auto-replace on, fill it now.
     * Anything still unfilled is reported to the Maintenance Managers straight away.
     */
    public function vacate(Vehicle $vehicle, ?Driver $former, string $reason): ?DriverVacancy
    {
        // Only a vehicle in service carries drivers; a unit that is not has no seat to fill.
        if ($vehicle->status !== 'active') {
            return null;
        }

        $existing = DriverVacancy::open()->where('vehicle_id', $vehicle->id)->where('former_driver_id', $former?->id)->first();

        if ($existing) {
            return $existing;
        }

        $vacancy = DriverVacancy::create([
            'vehicle_id' => $vehicle->id,
            'former_driver_id' => $former?->id,
            'reason' => $reason,
            // The drivers still on it, the other seats waiting to be filled, and this one.
            'target_drivers' => min(Vehicle::MAX_DRIVERS, $vehicle->drivers()->count() + DriverVacancy::open()->where('vehicle_id', $vehicle->id)->count() + 1),
            'opened_at' => now(),
        ]);

        Audit::log(Audit::FLEET, 'vacancy_opened',
            $vehicle->plate_number.' lost a driver ('.$vacancy->reasonLabel().($former ? ': '.$former->name : '').')',
            $vehicle, ['former_driver_id' => $former?->id, 'reason' => $reason]);

        if (config('fleet.auto_replace')) {
            $this->fill($vacancy);
        }

        if (! $vacancy->fresh()->resolved_at) {
            $this->reportOpen(collect([$vacancy->fresh()]));
        }

        return $vacancy->fresh();
    }

    /**
     * Give the vacancy to the best free driver. Returns the driver, or null when nobody could be
     * assigned (no free driver) or the vacancy is no longer needed.
     */
    public function fill(DriverVacancy $vacancy): ?Driver
    {
        $replacement = null;
        $vehicle = null;
        $other = null;

        DB::transaction(function () use ($vacancy, &$replacement, &$vehicle, &$other) {
            $vacancy = DriverVacancy::lockForUpdate()->find($vacancy->id);

            if (! $vacancy || $vacancy->resolved_at) {
                return;
            }

            $vehicle = Vehicle::lockForUpdate()->find($vacancy->vehicle_id);
            $current = $vehicle ? $vehicle->drivers()->pluck('drivers.id')->map(fn ($id) => (int) $id) : collect();

            // The unit left service, or the Maintenance Manager already put someone on it.
            if (! $vehicle || $vehicle->status !== 'active' || $current->count() >= min($vacancy->target_drivers, Vehicle::MAX_DRIVERS)) {
                $vacancy->update(['resolved_at' => now(), 'resolution' => DriverVacancy::NOT_NEEDED]);
                $vehicle = null;

                return;
            }

            foreach ($this->candidates() as $candidate) {
                // Re-checked inside the transaction: the driver may have been given a vehicle a moment ago.
                if (DB::table('driver_vehicle')->where('driver_id', $candidate->id)->exists()) {
                    continue;
                }

                $vehicle->drivers()->attach($candidate->id);

                if ($current->count() + 1 >= Vehicle::MAX_DRIVERS) {
                    $other = Driver::find($current->first());
                    $this->rebuildRoster($vehicle, $current->first(), $candidate->id);
                }

                $vacancy->update([
                    'resolved_at' => now(),
                    'resolution' => DriverVacancy::FILLED,
                    'replacement_driver_id' => $candidate->id,
                ]);

                $replacement = $candidate;

                return;
            }
        });

        if (! $replacement || ! $vehicle) {
            return null;
        }

        $vacancy = $vacancy->fresh(['vehicle', 'formerDriver', 'replacementDriver']);

        Audit::log(Audit::FLEET, 'replacement_assigned',
            $replacement->name.' assigned to '.$vehicle->plate_number.' to replace '.($vacancy->formerDriver?->name ?? 'a former driver').' ('.$vacancy->reasonLabel().')',
            $vehicle, ['replacement_driver_id' => $replacement->id, 'former_driver_id' => $vacancy->former_driver_id, 'reason' => $vacancy->reason, 'automatic' => true]);

        Notifier::send($replacement->user, new ReplacementAssigned($vehicle, $vacancy, $other));
        Notifier::toRole('maintenance_manager', new VehicleVacancyFilled($vacancy));

        return $replacement;
    }

    /** Try every open vacancy again, oldest first. @return array{filled: int, still_open: int} */
    public function retryOpen(): array
    {
        $filled = 0;

        foreach (DriverVacancy::open()->orderBy('opened_at')->get() as $vacancy) {
            if ($this->fill($vacancy)) {
                $filled++;
            }
        }

        return ['filled' => $filled, 'still_open' => DriverVacancy::open()->count()];
    }

    /**
     * Remind the Maintenance Managers of vacancies that have stayed open (once per reminder window).
     * Returns how many vacancies were reported.
     */
    public function remindOpen(): int
    {
        $due = DriverVacancy::open()->with(['vehicle', 'formerDriver'])
            ->where(fn ($q) => $q->whereNull('last_notified_at')
                ->orWhere('last_notified_at', '<', now()->subHours((int) config('fleet.reminder_hours', 24))))
            ->orderBy('opened_at')
            ->get();

        if ($due->isNotEmpty()) {
            $this->reportOpen($due);
        }

        return $due->count();
    }

    /**
     * Take every driver whose licence has expired off their vehicle, as a suspension would, and open
     * a vacancy for each seat. They keep their account; the Maintenance Manager reassigns them once the licence is renewed.
     *
     * @return Collection<int, Driver>  the drivers who were released
     */
    public function releaseExpiredLicences(): Collection
    {
        $released = collect();

        $drivers = Driver::where('is_approved', true)->where('is_rejected', false)->where('is_suspended', false)
            ->whereHas('vehicle')
            ->get()
            ->filter(fn (Driver $d) => $d->licenseState() === 'expired');

        foreach ($drivers as $driver) {
            $vehicles = $driver->releaseFromVehicles();

            Audit::log(Audit::DRIVER, 'released_from_vehicle',
                'Took '.$driver->name.' off '.$vehicles->pluck('plate_number')->implode(', ').' because their licence expired',
                $driver, ['licence_expired_on' => $driver->expiration_date, 'vehicles' => $vehicles->pluck('id')->all()]);

            Notifier::send($driver->user, new LicenceExpiredRelease($driver, $vehicles));

            foreach ($vehicles as $vehicle) {
                $this->vacate($vehicle, $driver, DriverVacancy::LICENCE_EXPIRED);
            }

            $released->push($driver);
        }

        return $released;
    }

    /** Tell the Maintenance Managers which vehicles are short of a driver, and who could take them. */
    private function reportOpen(Collection $vacancies): void
    {
        $vacancies = DriverVacancy::with(['vehicle', 'formerDriver'])->whereIn('id', $vacancies->pluck('id'))->orderBy('opened_at')->get();

        Notifier::toRole('maintenance_manager', new VehicleVacancyOpen($vacancies, $this->candidates()->take(3)));

        DriverVacancy::whereIn('id', $vacancies->pluck('id'))->update(['last_notified_at' => now()]);
    }

    /** From today on the two drivers take turns, the one already on the vehicle first. */
    private function rebuildRoster(Vehicle $vehicle, int $firstDriverId, int $secondDriverId): void
    {
        VehicleDutyDay::where('vehicle_id', $vehicle->id)->whereDate('duty_date', '>=', today())->delete();

        for ($i = 0; $i < (int) config('fleet.roster_days', 30); $i++) {
            VehicleDutyDay::create([
                'vehicle_id' => $vehicle->id,
                'driver_id' => $i % 2 === 0 ? $firstDriverId : $secondDriverId,
                'duty_date' => today()->addDays($i)->toDateString(),
            ]);
        }
    }
}
