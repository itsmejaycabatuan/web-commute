<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Models\VehicleLocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VehicleOfflineCheck extends Command
{
    protected $signature = 'vehicle:offline-check';
    protected $description = 'Mark vehicles inactive when no broadcast for > 5 minutes';

    public function handle(): int {
        $threshold = now()->subMinutes(5);
        $stale = VehicleLocation::where('last_update', '<', $threshold)
            ->distinct('vehicle_id')
            ->pluck('vehicle_id');
        foreach ($stale as $vid) {
            Vehicle::where('id', $vid)
                ->whereTrue('is_active')
                ->update(['is_active' => false]);
            Log::info("Vehicle {$vid} marked inactive.");
        }
        $recent = VehicleLocation::where('last_update', '>=', $threshold)
            ->distinct('vehicle_id')
            ->pluck('vehicle_id');
        foreach ($recent as $vid) {
            Vehicle::where('id', $vid)
                ->whereFalse('is_active')
                ->update(['is_active' => true]);
            Log::info("Vehicle {$vid} re-activated.");
        }
        return 0;
    }
}
