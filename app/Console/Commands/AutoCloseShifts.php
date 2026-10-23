<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Models\TimeKeeping;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoCloseShifts extends Command
{
    protected $signature = 'shifts:auto-close';

    protected $description = 'Close shifts a driver forgot to end (open too long, or past midnight) and flag them for review';

    public function handle(): int
    {
        $maxHours = (float) config('timekeeping.auto_close_after_hours', 14);
        $atMidnight = (bool) config('timekeeping.auto_close_at_midnight', true);
        $credit = (float) config('timekeeping.auto_close_credit_hours', 8);

        $closed = 0;
        $drivers = [];

        foreach (TimeKeeping::open()->with('driver')->get() as $entry) {
            $start = $entry->startedAt();

            if (! $start || ! $entry->driver) {
                continue;
            }

            $tooLong = $start->copy()->addMinutes((int) round($maxHours * 60))->lte(now());
            $pastMidnight = $atMidnight && $start->copy()->endOfDay()->lt(now());

            if (! $tooLong && ! $pastMidnight) {
                continue;
            }

            // Nobody knows when the driver really stopped: credit a regular shift (never more than
            // has actually elapsed) with no overtime, and leave it for the Driver Manager to check.
            $hours = round(min($credit, $start->diffInMinutes(now()) / 60), 2);
            $end = $start->copy()->addMinutes((int) round($hours * 60));

            $entry->update([
                'time_out' => $end->format('h:i A'),
                'hours_worked' => $hours,
                'overtime_hours' => 0,
                'auto_closed_at' => now(),
                'reviewed_at' => null,
            ]);

            $drivers[$entry->driver_id] = $entry;
            $closed++;
        }

        foreach ($drivers as $driverId => $entry) {
            $this->endDriverSession($entry->driver, $entry);
        }

        $this->info("Auto-closed {$closed} shift(s).");

        return self::SUCCESS;
    }

    /**
     * With the shift closed the driver goes inactive and the vehicle stops broadcasting. A driver who
     * has since started a newer shift is still on the road, so they are left alone.
     */
    private function endDriverSession(Driver $driver, TimeKeeping $entry): void
    {
        if (TimeKeeping::open()->where('driver_id', $driver->id)->exists()) {
            return;
        }

        if ($driver->status === 'active') {
            $driver->update(['status' => 'inactive']);
        }

        $vehicleId = $entry->vehicle_id ?? $driver->assignedVehicle()?->id;

        if (! $vehicleId) {
            return;
        }

        $stillDriven = TimeKeeping::open()->where('vehicle_id', $vehicleId)->exists();

        if ($stillDriven) {
            return;
        }

        VehicleLocation::where('vehicle_id', $vehicleId)->delete();
        Vehicle::where('id', $vehicleId)->update(['is_active' => false]);

        Log::info("Vehicle {$vehicleId} taken off the map: driver {$driver->id}'s shift was auto-closed.");
    }
}
