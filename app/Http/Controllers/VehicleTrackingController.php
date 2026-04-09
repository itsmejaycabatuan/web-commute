<?php

namespace App\Http\Controllers;

use App\Events\LocationUpdated;
use App\Models\VehicleLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VehicleTrackingController extends Controller
{
    public function broadcastLocation(Request $request) {
        // Log everything to see what's coming in

        $validated = $request->validate([
            'vehicle_id' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
        ]);

        event(new LocationUpdated(
            $validated['vehicle_id'],
            $validated['longitude'],
            $validated['latitude'],
            $validated['speed'],
            $validated['accuracy']
        ));

        VehicleLocation::updateOrCreate(
            ['vehicle_id' => $validated['vehicle_id']],
            [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'speed' => $validated['speed'] ?? null,
                'accuracy' => $validated['accuracy'] ?? null,
                'last_update' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'request' => $validated
        ]);
    }

    public function getActiveVehicles() {
        $vehicles = VehicleLocation::where('last_update', '>=', now()->subMinutes(5))->get();

        return response()->json($vehicles);
    }
}
