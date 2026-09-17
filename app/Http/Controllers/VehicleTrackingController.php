<?php

namespace App\Http\Controllers;

use App\Events\LocationUpdated;
use App\Jobs\RetryBroadcastLocation;
use App\Models\VehicleLocation;
use App\Models\VehicleLocationHistory;
use Illuminate\Http\Request;

class VehicleTrackingController extends Controller
{
    private function haversineDistance($lat1, $lon1, $lat2, $lon2, $unit = 'km')
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;

        if ($unit == 'km') {
            return $miles * 1.609344;
        } elseif ($unit == 'm') {
            return $miles * 1.609344 * 1000;
        } else {
            return $miles;
        }
    }

    public function broadcastLocation(Request $request)
    {
        activity()->event('Broadcastlocation')->log('Action performed: broadcastLocation');
        $validated = $request->validate([
            'vehicle_id' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
            'timestamp' => 'nullable|integer',
            'user_id' => 'required|integer',
        ]);

        $duplicate = false;
        if (! empty($validated['timestamp'])) {
            $existing = VehicleLocation::where('vehicle_id', $validated['vehicle_id'])
                ->where('broadcast_timestamp', $validated['timestamp'])
                ->exists();
            if ($existing) {
                \Log::warning('Duplicate broadcast ignored', [
                    'vehicle_id' => $validated['vehicle_id'],
                    'timestamp' => $validated['timestamp'],
                ]);
                $duplicate = true;

                return response()->json([
                    'success' => true,
                    'duplicate' => true,
                    'message' => 'Broadcast already recorded at this timestamp.',
                ], 200);
            }
        }

        try {
            $now = now();
            if (! empty($validated['timestamp'])) {
                $vehicleLocation = VehicleLocation::updateOrCreate(
                    [
                        'vehicle_id' => $validated['vehicle_id'],
                        'broadcast_timestamp' => $validated['timestamp'],
                    ],
                    [
                        'user_id' => $validated['user_id'],
                        'latitude' => $validated['latitude'],
                        'longitude' => $validated['longitude'],
                        'speed' => $validated['speed'] ?? null,
                        'accuracy' => $validated['accuracy'] ?? null,
                        'last_update' => $now,
                    ]
                );
            } else {
                $vehicleLocation = VehicleLocation::updateOrCreate(
                    ['vehicle_id' => $validated['vehicle_id']],
                    [
                        'user_id' => $validated['user_id'],
                        'latitude' => $validated['latitude'],
                        'longitude' => $validated['longitude'],
                        'speed' => $validated['speed'] ?? null,
                        'accuracy' => $validated['accuracy'] ?? null,
                        'last_update' => $now,
                    ]
                );
            }

            Vehicle::where('id', $validated['vehicle_id'])
                ->update(['is_active' => true]);

            event(new LocationUpdated(
                $validated['vehicle_id'],
                $validated['longitude'],
                $validated['latitude'],
                $validated['speed'] ?? null,
                $validated['accuracy'] ?? null,
                $validated['user_id']
            ));

            VehicleLocationHistory::create([
                'vehicle_location_id' => $vehicleLocation->id,
                'longitude' => $validated['longitude'],
                'latitude' => $validated['latitude'],
                'user_id' => $validated['user_id'],
                'distance_from_last_pos' => 0,
            ]);

            return response()->json([
                'success' => true,
                'duplicate' => $duplicate,
                'location_id' => $vehicleLocation->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('broadcastLocation failure – scheduling retry', [
                'vehicle_id' => $validated['vehicle_id'],
                'exception' => $e,
            ]);
            RetryBroadcastLocation::dispatch($validated)->delay(now()->addSeconds(5));

            return response()->json([
                'success' => false,
                'retry' => true,
                'retry_after' => 5,
                'message' => 'Temporary failure – retrying in 5 seconds.',
            ], 409, ['Retry-After' => '5']);
        }
    }

    public function getActiveVehicles()
    {
        $vehicles = VehicleLocation::where('last_update', '>=', now()->subMinutes(5))->get();

        activity()->event('Getactivevehicles')->log('Action performed: getActiveVehicles');

        return response()->json($vehicles);
    }
}
