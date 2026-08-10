<?php

use App\Models\DevMarker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// routes/api.php
// routes/api.php
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
                Log::info('DEV MARKER DEBUG', [
                    'marker_user_id' => $m->user_id,
                    'driver_found' => $driver ? true : false,
                    'driver_status' => $driver?->status,
                    'driver_getAttributes' => $driver ? $driver->getAttributes() : null,
                ]);
                $vehicle = $driver?->vehicle?->first();

                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'lat' => (float) $m->lat,
                    'lng' => (float) $m->lng,
                    'marker_status' => $m->status,
                    'driver_status' => $driver?->status ?? 'unknown',
                    'plate_number' => $vehicle?->plate_number ?? 'N/A',
                    'vehicle_type' => $vehicle?->type ?? 'N/A',
                    'route' => 'Minglanilla → IT Park',
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
