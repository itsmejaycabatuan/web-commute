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

            $vehicleHistory = VehicleLocationHistory::where('vehicle_location_id', $vehicleLocation->id)->latest()->take(2)->get();

            $longitude1 = $vehicleHistory ? $vehicleHistory[0]->longitude : null;
            $latitude1 = $vehicleHistory ? $vehicleHistory[0]->latitude : null;
            $longitude2 = $vehicleHistory ? $vehicleHistory[1]->longitude : null;
            $latitude2 = $vehicleHistory ? $vehicleHistory[1]->latitude : null;
            $distance = $this->haversineDistance($latitude1, $longitude1, $latitude2, $longitude2) ? $this->haversineDistance($latitude1, $longitude1, $latitude2, $longitude2) : null;
            $speed = ($distance / 5) * 3.6;

            event(new LocationUpdated(
                $validated['vehicle_id'],
                $validated['longitude'],
                $validated['latitude'],
                $speed,
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
