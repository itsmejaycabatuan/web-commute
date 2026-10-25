<?php

namespace App\Http\Controllers;

use App\Events\LocationUpdated;
use App\Helpers\LocationPrivacy;
use App\Jobs\RetryBroadcastLocation;
use App\Models\Vehicle;
use App\Models\VehicleLocation;
use App\Models\VehicleLocationHistory;
use App\Support\Geo;
use Illuminate\Http\Request;

class VehicleTrackingController extends Controller
{
    /** A gap longer than this between two pings is a break in the trip, not distance driven. */
    private const MAX_PING_GAP_MINUTES = 10;

    /**
     * Kilometres driven since the vehicle's previous recorded position (0 for the first ping of a trip,
     * after a long silence, or for an implausible jump). Feeds the driver's distance-today figure and the
     * fleet analytics.
     */
    private function distanceFromLastPosition(array $validated, VehicleLocation $current, ?VehicleLocationHistory $previous): float
    {
        if (! $previous || ! $previous->created_at || $previous->created_at->lt(now()->subMinutes(self::MAX_PING_GAP_MINUTES))) {
            return 0.0;
        }

        $km = Geo::distanceKm((float) $previous->latitude, (float) $previous->longitude, (float) $validated['latitude'], (float) $validated['longitude']);

        // 10 minutes at 120 km/h is the most a jeepney could cover; more than that is a GPS glitch.
        return $km > 20 ? 0.0 : round($km, 4);
    }

    public function broadcastLocation(Request $request)
    {

        $validated = $request->validate([
            'vehicle_id' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'accuracy' => 'nullable|numeric',
            'timestamp' => 'nullable|integer',
            'user_id' => 'required|integer',
        ]);

        // ── Only the assigned driver may broadcast a vehicle position ──
        $user = $request->user() ?: auth()->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Sign in as a driver to broadcast your vehicle location.',
            ], 403);
        }

        if (! $user->hasRole('driver') || ! $user->driver) {
            return response()->json([
                'success' => false,
                'message' => 'Only drivers can broadcast a vehicle location.',
            ], 403);
        }

        // UCN_SC_E022 — a pending or rejected driver may not broadcast.
        if (! $user->driver->is_approved || $user->driver->is_rejected) {
            return response()->json([
                'success' => false,
                'message' => 'Your driver account is not approved.',
            ], 403);
        }

        // UCN_SC_E012 — a suspended driver stops broadcasting immediately.
        if ($user->driver->isSuspended()) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is suspended.',
            ], 403);
        }

        // The system closed this shift because the driver never clocked out: the feed stops with it.
        if ($user->driver->shiftWasAutoClosed()) {
            return response()->json([
                'success' => false,
                'message' => 'Your shift was closed automatically. Clock in on your next shift to resume.',
            ], 403);
        }

        $vehicle = Vehicle::find($validated['vehicle_id']);

        if (! $vehicle || ! $vehicle->drivers()->where('drivers.id', $user->driver->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This vehicle is not assigned to you.',
            ], 403);
        }

        // A shared vehicle is only driven by the driver whose duty day it is.
        if (($vehicle->dutyDriverOn()?->id) !== $user->driver->id) {
            return response()->json([
                'success' => false,
                'message' => 'Today is not your duty day on this vehicle.',
            ], 403);
        }

        // A vehicle that is inactive, under maintenance or disposed is out of
        // service: its driver does not appear on the live map.
        if ($vehicle->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This vehicle is not in service.',
            ], 403);
        }

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

        // The vehicle's previous recorded position, taken before this broadcast replaces or joins it.
        $previous = VehicleLocationHistory::whereHas('vehicleLocation', fn ($q) => $q->where('vehicle_id', $validated['vehicle_id']))
            ->latest('id')
            ->first();

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

            // NOTE: the constructor signature is (trackingId, latitude, longitude, ...)
            // NOTE: the constructor signature is (trackingId, latitude, longitude, ...)
            event(new LocationUpdated(
                $validated['vehicle_id'],
                $validated['latitude'],
                $validated['longitude'],
                $validated['speed'] ?? null,
                $validated['accuracy'] ?? null,
                $validated['user_id'],
                $user->driver->status
            ));

            VehicleLocationHistory::create([
                'vehicle_location_id' => $vehicleLocation->id,
                'longitude' => $validated['longitude'],
                'latitude' => $validated['latitude'],
                'user_id' => $validated['user_id'],
                'distance_from_last_pos' => $this->distanceFromLastPosition($validated, $vehicleLocation, $previous ?? null),
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
        // Drivers are the source of this data, not a consumer of it.
        if (auth()->check() && auth()->user()->hasRole('driver')) {
            return response()->json(['success' => true, 'vehicles' => []]);
        }

        // Newest update per vehicle, active in the last 5 minutes.
        // UCN_SC_E015 postcondition: a unit that is under maintenance or already
        // disposed drops off the live map immediately, regardless of when it last
        // reported a position.
        $trackableIds = Vehicle::whereIn('status', ['active', 'inactive'])->pluck('id');

        $locations = VehicleLocation::where('last_update', '>=', now()->subMinutes(5))
            ->whereIn('vehicle_id', $trackableIds)
            ->orderBy('last_update', 'desc')
            ->get()
            ->unique('vehicle_id');

        $vehicles = $locations->map(function ($location) {
            $vehicle = Vehicle::with('drivers')->find($location->vehicle_id);

            // Guests/commuters must never receive the raw GPS coordinates
            $private = LocationPrivacy::obfuscate(
                (float) $location->latitude,
                (float) $location->longitude
            );

            return [
                'id' => $location->vehicle_id,
                'vehicle_id' => $location->vehicle_id,
                'latitude' => $private['lat'],
                'longitude' => $private['lng'],
                'lat' => $private['lat'],
                'lng' => $private['lng'],
                'privacy_radius' => $private['privacy_radius'],
                'plate_number' => $vehicle->plate_number ?? null,
                'route_name' => $vehicle->location ?? 'N/A',
                // The driver's availability toggle (active / inactive): drives the
                // marker colour and the status line in the popup.
                'driver_status' => $this->dutyDriverStatus($vehicle),
                'speed' => $location->speed,
                'accuracy' => $location->accuracy,
                'last_update' => $location->last_update ? (string) $location->last_update : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Availability of the vehicle's driver for the marker: the driver on duty today, or, when the
     * roster names nobody, the first of its drivers that is active.
     */
    private function dutyDriverStatus(?Vehicle $vehicle): string
    {
        if (! $vehicle || $vehicle->drivers->isEmpty()) {
            return 'unknown';
        }

        $onDuty = $vehicle->dutyDriverOn();

        return ($onDuty ?? $vehicle->drivers->firstWhere('status', 'active') ?? $vehicle->drivers->first())->status ?? 'unknown';
    }
}
