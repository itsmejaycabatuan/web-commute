<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleDutyDay;
use App\Support\Audit;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The duty roster (Maintenance Manager): on a vehicle shared by two drivers, which of them drives it on
 * which day. The manager generates an alternating schedule and edits or swaps individual days. A driver
 * can only clock in on a vehicle on a day the roster gives to them (Driver::dutyBlock()).
 */
class DutyRosterController extends Controller
{
    /** Days shown per page. */
    private const WINDOW = 28;

    public function index(Request $request)
    {
        // Only vehicles shared by two drivers have a roster.
        $vehicles = Vehicle::whereHas('drivers', null, '>=', Vehicle::MAX_DRIVERS)
            ->with('drivers')
            ->orderBy('plate_number')
            ->get();

        $vehicle = $vehicles->firstWhere('id', (int) $request->query('vehicle_id')) ?? $vehicles->first();

        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : today();
        $to = $from->copy()->addDays(self::WINDOW - 1);

        $days = collect();
        $dutyToday = null;

        if ($vehicle) {
            $roster = $vehicle->dutyDays()
                ->whereBetween('duty_date', [$from->toDateString(), $to->toDateString()])
                ->get()
                ->keyBy(fn ($d) => $d->duty_date->toDateString());

            for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                $days->push([
                    'date' => $date->toDateString(),
                    'label' => $date->format('M d, Y'),
                    'weekday' => $date->format('l'),
                    'is_today' => $date->isToday(),
                    'is_past' => $date->isPast() && ! $date->isToday(),
                    'driver_id' => $roster->get($date->toDateString())?->driver_id,
                ]);
            }

            $dutyToday = $vehicle->dutyDriverOn();
        }

        return view('maintenance-manager.duty-roster', [
            'vehicles' => $vehicles,
            'vehicle' => $vehicle,
            'days' => $days,
            'from' => $from,
            'prevFrom' => $from->copy()->subDays(self::WINDOW)->toDateString(),
            'nextFrom' => $from->copy()->addDays(self::WINDOW)->toDateString(),
            'dutyToday' => $dutyToday,
        ]);
    }

    /** Fill a run of days with the two drivers taking turns. Replaces whatever those days held. */
    public function generate(Request $request, Vehicle $vehicle)
    {
        $driverIds = $this->twoDriverIds($vehicle);

        $validated = $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'first_driver_id' => 'required|in:'.implode(',', $driverIds),
            'days' => 'required|integer|min:2|max:120',
        ], [
            'start_date.after_or_equal' => 'The schedule cannot start in the past.',
            'first_driver_id.in' => 'Choose one of the two drivers on this vehicle.',
            'days.max' => 'Generate at most 120 days at a time.',
        ]);

        $first = (int) $validated['first_driver_id'];
        $second = collect($driverIds)->first(fn ($id) => $id !== $first);
        $start = Carbon::parse($validated['start_date'])->startOfDay();

        try {
            DB::transaction(function () use ($vehicle, $validated, $first, $second, $start) {
                for ($i = 0; $i < (int) $validated['days']; $i++) {
                    VehicleDutyDay::updateOrCreate(
                        ['vehicle_id' => $vehicle->id, 'duty_date' => $start->copy()->addDays($i)->toDateString()],
                        ['driver_id' => $i % 2 === 0 ? $first : $second]
                    );
                }
            });
        } catch (QueryException $e) {
            report($e);

            return back()->with('error', 'A database error occurred. The schedule was not generated.');
        }

        Audit::log(Audit::FLEET, 'duty_roster_updated', 'Generated an alternating duty roster for '.$vehicle->plate_number, $vehicle, [
            'from' => $start->toDateString(),
            'days' => (int) $validated['days'],
            'first_driver_id' => $first,
        ]);

        return back()->with('success', 'Alternating schedule generated for '.$validated['days'].' days.');
    }

    /**
     * Save the days shown on the page: each date is given to one of the two drivers (a swap) or cleared.
     * Past days are history and are left alone.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $driverIds = $this->twoDriverIds($vehicle);

        $validated = $request->validate([
            'days' => 'required|array|max:366',
            'days.*' => ['nullable', 'in:'.implode(',', $driverIds)],
        ], [
            'days.*.in' => 'Each day goes to one of the two drivers on this vehicle, or is left empty.',
        ]);

        $changed = 0;

        try {
            DB::transaction(function () use ($vehicle, $validated, &$changed) {
                foreach ($validated['days'] as $date => $driverId) {
                    try {
                        $day = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                    } catch (\Throwable) {
                        throw ValidationException::withMessages(['days' => 'Invalid date '.$date.'.']);
                    }

                    if ($day->isPast() && ! $day->isToday()) {
                        continue;
                    }

                    $existing = VehicleDutyDay::where('vehicle_id', $vehicle->id)->whereDate('duty_date', $day->toDateString())->first();

                    if (blank($driverId)) {
                        if ($existing) {
                            $existing->delete();
                            $changed++;
                        }
                    } elseif (! $existing) {
                        VehicleDutyDay::create(['vehicle_id' => $vehicle->id, 'driver_id' => (int) $driverId, 'duty_date' => $day->toDateString()]);
                        $changed++;
                    } elseif ($existing->driver_id !== (int) $driverId) {
                        $existing->update(['driver_id' => (int) $driverId]);
                        $changed++;
                    }
                }
            });
        } catch (QueryException $e) {
            report($e);

            return back()->with('error', 'A database error occurred. The roster was not saved.');
        }

        if ($changed) {
            Audit::log(Audit::FLEET, 'duty_roster_updated', 'Changed the duty roster of '.$vehicle->plate_number, $vehicle, ['days_changed' => $changed]);
        }

        return back()->with('success', $changed ? "Roster saved ($changed ".($changed === 1 ? 'day' : 'days').' changed).' : 'Nothing to change.');
    }

    /** The ids of the vehicle's two drivers; a roster only exists for a vehicle that has both. */
    private function twoDriverIds(Vehicle $vehicle): array
    {
        $ids = $vehicle->drivers()->pluck('drivers.id')->map(fn ($id) => (int) $id)->all();

        if (count($ids) < Vehicle::MAX_DRIVERS) {
            abort(422, 'A duty roster needs two drivers assigned to the vehicle.');
        }

        return $ids;
    }
}
