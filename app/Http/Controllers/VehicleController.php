<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\TimeKeeping;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    /**
     * A vehicle may only carry a driver while it is roadworthy. Maintenance and
     * disposed units are out of service (UCN_SC_E015 E4).
     */
    private const ASSIGNABLE_STATUSES = ['active', 'inactive'];

    public function index()
    {
        activity()->event('Index')->log('Action performed: index');
        $vehicles = Vehicle::with('driver')->latest()->get()->map(fn ($vehicle) => $this->formatVehicle($vehicle));

        $drivers = Driver::orderBy('name')->get();

        return view('maintenance-manager.vehicles', compact('vehicles', 'drivers'));
    }

    /**
     * The vehicle payload shared by the page and the JSON responses the
     * page's fetch() calls read (add / edit / delete from the modal).
     */
    private function formatVehicle(Vehicle $vehicle): array
    {
        return [
            'id' => $vehicle->id,
            'driver_id' => $vehicle->driver_id,
            'year' => $vehicle->year,
            'brand' => $vehicle->brand,
            'model' => $vehicle->model,
            'plate_number' => $vehicle->plate_number,
            'status' => $vehicle->status,
            'fuel_type' => $vehicle->fuel_type,
            'tank_capacity' => $vehicle->tank_capacity,
            'vin' => $vehicle->vin,
            'location' => $vehicle->location,
            'acquisition_date' => $vehicle->acquisition_date?->format('M d, Y'),
            'acquisition_date_raw' => $vehicle->acquisition_date?->toDateString(),
            'exp_disposal_date' => $vehicle->exp_disposal_date?->format('M d, Y'),
            'exp_disposal_date_raw' => $vehicle->exp_disposal_date?->toDateString(),
            'driver_name' => $vehicle->driver?->name,
            'created_at' => $vehicle->created_at?->format('M d, Y'),
            'updated_at' => $vehicle->updated_at?->format('M d, Y'),
        ];
    }

    public function store(Request $request)
    {
        activity()->event('Store')->log('Action performed: store');
        $validated = $request->validate([
            'year' => 'required|integer|min:1990|max:2030',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'vin' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
            'tank_capacity' => 'nullable|string|max:20',
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'location' => 'nullable|string|max:150',
            'status' => 'required|in:active,maintenance,inactive,disposed',
            'acquisition_date' => 'required|date',
            'exp_disposal_date' => 'nullable|date|after:acquisition_date',
        ]);

        $this->assertDriverAssignable($validated['status'], $validated['driver_id'] ?? null);

        // Postcondition: a unit taken out of service loses its driver.
        if (! in_array($validated['status'], self::ASSIGNABLE_STATUSES, true)) {
            $validated['driver_id'] = null;
        }

        $vehicle = Vehicle::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Vehicle successfully added.',
                'vehicle' => $this->formatVehicle($vehicle->load('driver')),
            ], 201);
        }

        return back()->with('success', 'Vehicle successfully added.');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        activity()->event('Update')->log('Action performed: update');
        $validated = $request->validate([
            'year' => 'required|integer|min:1990|max:2030',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number,' . $vehicle->id,
            'vin' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
            'tank_capacity' => 'nullable|string|max:20',
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'location' => 'nullable|string|max:150',
            'status' => 'required|in:active,maintenance,inactive,disposed',
            'acquisition_date' => 'required|date',
            'exp_disposal_date' => 'nullable|date|after:acquisition_date',
        ]);

        $this->assertDriverAssignable($validated['status'], $validated['driver_id'] ?? null, $vehicle);
        $this->assertNotDisposedWhileInUse($vehicle, $validated['status']);

        // Postcondition: a unit taken out of service loses its driver.
        if (! in_array($validated['status'], self::ASSIGNABLE_STATUSES, true)) {
            $validated['driver_id'] = null;
        }

        $vehicle->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Vehicle successfully updated.',
                'vehicle' => $this->formatVehicle($vehicle->load('driver')),
            ]);
        }

        return back()->with('success', 'Vehicle successfully updated.');
    }

    public function destroy(Request $request, Vehicle $vehicle)
    {
        activity()->event('Destroy')->log('Action performed: destroy');

        // Postcondition: a unit that has a service history is marked Disposed
        // instead of being erased, so trip and maintenance logs stay auditable.
        if ($vehicle->maintenanceLogs()->exists() || $vehicle->preventiveMaintenances()->exists()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This vehicle has maintenance records and cannot be deleted. Mark its status as Disposed instead.',
                ], 409);
            }

            return back()->with(
                'error',
                'This vehicle has maintenance records and cannot be deleted. Mark its status as Disposed instead.'
            );
        }

        $vehicle->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Vehicle successfully deleted.']);
        }

        return back()->with('success', 'Vehicle successfully deleted.');
    }

    /**
     * E4 — Assigning an under-maintenance vehicle: a driver may only be assigned
     * to a roadworthy unit.
     */
    private function assertDriverAssignable(?string $status, ?int $driverId, ?Vehicle $vehicle = null): void
    {
        if (! $driverId) {
            return;
        }

        // Keeping the current driver while taking the unit out of service is
        // allowed — the driver is simply detached once the status is saved.
        if ($vehicle && $vehicle->driver_id === $driverId) {
            return;
        }

        if (! in_array($status, self::ASSIGNABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'driver_id' => 'A driver cannot be assigned to a vehicle with a '.strtolower((string) $status).' status.',
            ]);
        }
    }

    /**
     * E3 — Cannot dispose active vehicle: the assigned driver must not still be
     * clocked into the unit.
     */
    private function assertNotDisposedWhileInUse(Vehicle $vehicle, ?string $newStatus): void
    {
        if ($newStatus !== 'disposed' || ! $vehicle->driver_id) {
            return;
        }

        $onShift = TimeKeeping::where('driver_id', $vehicle->driver_id)
            ->whereDate('date', today())
            ->whereNull('time_out')
            ->exists();

        if ($onShift) {
            throw ValidationException::withMessages([
                'status' => 'Cannot dispose a vehicle that a driver is currently clocked into. Clock the driver out first.',
            ]);
        }
    }
}
