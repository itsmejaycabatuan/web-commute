<?php

use App\Helpers\LocationPrivacy;
use App\Models\DevMarker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/markers', function () {
    if (! app()->environment('local')) {
        return [];
    }
    try {
        return DevMarker::with('driver.vehicle')
            ->select('id', 'user_id', 'name', 'lat', 'lng', 'status')
            ->get()
            ->map(function ($m) {
                $driver = $m->driver;
                $vehicle = $driver?->vehicle?->first();

                // Always obfuscate for the map API so commuters/guests never see exact coords
                $private = LocationPrivacy::obfuscate((float) $m->lat, (float) $m->lng);

                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'lat' => $private['lat'],
                    'lng' => $private['lng'],
                    'marker_status' => $m->status,
                    'driver_status' => $driver?->status ?? 'unknown',
                    'plate_number' => $vehicle?->plate_number ?? 'N/A',
                    'vehicle_type' => $vehicle?->type ?? 'N/A',
                    'route' => 'Minglanilla → IT Park',
                    'privacy_radius' => $private['privacy_radius'],
                ];
            });
    } catch (Exception $e) {
        Log::error('DEV MARKER ERROR: ' . $e->getMessage());

        return [];
    }
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
