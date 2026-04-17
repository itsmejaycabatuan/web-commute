<?php

namespace App\Http\Controllers;

use App\Events\LocationUpdated;
use App\Models\VehicleLocation;
use App\Models\VehicleLocationHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VehicleTrackingController extends Controller
{


    private function haversineDistance($lat1, $lon1, $lat2, $lon2, $unit = 'km') {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + 
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
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

    public function broadcastLocation(Request $request) {
        // Log everything to see what's coming in

        $validated = $request->validate([
            'vehicle_id' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
        ]);
        
        $vehicleLocation = VehicleLocation::updateOrCreate(
            [
                'vehicle_id' => $validated['vehicle_id']
                ],
            [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'speed' => $validated['speed'] ?? null,
                'accuracy' => $validated['accuracy'] ?? null,
                'last_update' => now()
                ]
            );

            // Get last 2 history records safely
            $vehicleHistory = VehicleLocationHistory::where('vehicle_location_id', $vehicleLocation->id)
                ->orderBy('created_at', 'desc') // or 'id', 'desc' depending on your table
                ->take(2)
                ->get();

             // Initialize variables with null values
            $longitude1 = null;
            $latitude1 = null;
            $longitude2 = null;
            $latitude2 = null;
            $distance = null;
            $speed = null;
            
            // Safely access history records if they exist
            if ($vehicleHistory->count() > 0) {
                $longitude1 = $vehicleHistory[0]->longitude;
                $latitude1 = $vehicleHistory[0]->latitude;
            }
            
            if ($vehicleHistory->count() > 1) {
                $longitude2 = $vehicleHistory[1]->longitude;
                $latitude2 = $vehicleHistory[1]->latitude;
            }
            
            // Calculate distance only if both points exist
            if ($latitude1 && $longitude1 && $latitude2 && $longitude2) {
                $distance = $this->haversineDistance($latitude1, $longitude1, $latitude2, $longitude2);
                // Calculate speed: distance (meters) / 5 seconds * 3.6 = km/h
                $speed = ($distance / 5) * 3.6;
            }
            
            // Use the speed from request if calculation failed, otherwise use calculated speed
            $finalSpeed = $speed ?? ($validated['speed'] ?? 0);

            event(new LocationUpdated(
                $validated['vehicle_id'],
                $validated['longitude'],
                $validated['latitude'],
                $finalSpeed,
                $validated['accuracy']
            ));

        VehicleLocationHistory::create([
            'vehicle_location_id' => $vehicleLocation->id,
            'longitude' => $validated['longitude'],
            'latitude' => $validated['latitude'],
        ]);

        return response()->json([
            'success' => true,
            'request' => $validated,
            'history' => $vehicleHistory
        ]);
    }

    public function getActiveVehicles() {
        $vehicles = VehicleLocation::where('last_update', '>=', now()->subMinutes(5))->get();

        return response()->json($vehicles);
    }

}
