<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Support\Geo;

/**
 * Finds the vehicle a commuter is standing next to: the nearest active vehicle whose driver's phone is
 * broadcasting inside the configured radius. Used to attribute a fare to a vehicle and its driver on duty,
 * and to refuse a scan made where no PUJ is.
 */
class NearbyVehicleFinder
{
    /**
     * @param  float|null  $accuracyMeters  the phone's reported GPS accuracy; a vaguer fix widens the radius a little (capped)
     * @return array{vehicle: Vehicle, driver: ?Driver, distance_m: int}|null
     */
    public function find(float $latitude, float $longitude, ?float $accuracyMeters = null): ?array
    {
        $radius = (int) config('qr.nearby_meters', 150) + (int) min(max((float) $accuracyMeters, 0), 100);

        // A bounding box first keeps the exact distance maths to the handful of vehicles close by.
        $latDelta = $radius / 111_320;
        $lngDelta = $radius / (111_320 * max(cos(deg2rad($latitude)), 0.01));

        $latest = VehicleLocation::where('last_update', '>=', now()->subMinutes((int) config('qr.broadcast_fresh_minutes', 5)))
            ->whereBetween('latitude', [$latitude - $latDelta, $latitude + $latDelta])
            ->whereBetween('longitude', [$longitude - $lngDelta, $longitude + $lngDelta])
            ->orderByDesc('last_update')
            ->get()
            ->unique('vehicle_id');

        $best = null;

        foreach ($latest as $location) {
            $meters = Geo::distanceMeters($latitude, $longitude, (float) $location->latitude, (float) $location->longitude);

            if ($meters > $radius || ($best && $meters >= $best['distance_m'])) {
                continue;
            }

            $vehicle = Vehicle::with('drivers')->where('status', 'active')->find($location->vehicle_id);

            if (! $vehicle) {
                continue;
            }

            $best = ['vehicle' => $vehicle, 'driver' => $vehicle->dutyDriverOn(), 'distance_m' => (int) round($meters)];
        }

        return $best;
    }
}
