<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\TimeKeeping;
use App\Models\Vehicle;
use App\Models\VehicleDutyDay;
use App\Models\VehicleScanSession;
use App\Services\MaintenanceDueService;
use App\Support\Audit;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    /**
     * A vehicle may only carry a driver while it is in service. Inactive,
     * maintenance and disposed units are out of service (UCN_SC_E015 E4).
     */
    private const ASSIGNABLE_STATUSES = ['active'];

    public function index()
    {
        $due = app(MaintenanceDueService::class)->fleet();

        $vehicles = Vehicle::with('drivers')->latest()->get()
            ->map(fn ($vehicle) => $this->formatVehicle($vehicle, $due->get($vehicle->id)));

        // Only drivers who can actually drive: approved and not suspended. The page
        // further hides the ones already assigned to another vehicle.
        $drivers = Driver::where('is_approved', true)
            ->where('is_rejected', false)
            ->where('is_suspended', false)
            ->orderBy('name')
            ->with('user:id,email')
            ->get()
            ->map(fn (Driver $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'email' => $d->user?->email,
                'driver_code' => $d->driver_code,
            ]);

        $scanAvailable = VehicleScanController::available();

        return view('maintenance-manager.vehicles', compact('vehicles', 'drivers', 'scanAvailable'));
    }

    /**
     * The vehicle payload shared by the page and the JSON responses the
     * page's fetch() calls read (add / edit / delete from the modal).
     */
    private function formatVehicle(Vehicle $vehicle, ?array $due = null): array
    {
        $ordered = $vehicle->drivers->sortBy('pivot.id')->values();

        return [
            'id' => $vehicle->id,
            // Up to two drivers share a vehicle; `driver_id` / `second_driver_id` feed the form's two selects.
            'driver_id' => $ordered->get(0)?->id,
            'second_driver_id' => $ordered->get(1)?->id,
            'driver_ids' => $ordered->pluck('id')->all(),
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
            'driver_name' => $vehicle->driver_name,
            'driver_names' => $ordered->pluck('name')->all(),
            'maintenance_overdue' => $due ? $due['overdue']->pluck('task')->all() : [],
            'maintenance_due_soon' => $due ? $due['due_soon']->pluck('task')->all() : [],
            'created_at' => $vehicle->created_at?->format('M d, Y'),
            'updated_at' => $vehicle->updated_at?->format('M d, Y'),
        ];
    }

    public function store(Request $request)
    {
        // A vehicle filled from scanned papers is only accepted once the manager has said they checked it.
        $scan = $this->resolveScan($request);

        $validated = $request->validate([
            'year' => 'required|integer|min:1990|max:2030',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'vin' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
            'tank_capacity' => 'nullable|string|max:20',
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'second_driver_id' => ['nullable', 'exists:drivers,id', 'different:driver_id'],
            'location' => 'nullable|string|max:150',
            'status' => 'required|in:active,maintenance,inactive,disposed',
            'acquisition_date' => 'required|date',
            'exp_disposal_date' => 'nullable|date|after:acquisition_date',
        ]);

        $driverIds = $this->driverIds($validated);
        $this->assertDriversAssignable($validated['status'], $driverIds);

        // Postcondition: a unit taken out of service loses its drivers.
        if (! in_array($validated['status'], self::ASSIGNABLE_STATUSES, true)) {
            $driverIds = [];
        }

        $vehicle = Vehicle::create(Arr::except($validated, ['driver_id', 'second_driver_id']));
        $this->syncDrivers($vehicle, $driverIds);

        if ($scan) {
            $this->consumeScan($scan, $vehicle, $validated);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Vehicle successfully added.',
                'vehicle' => $this->formatVehicle($vehicle->load('drivers')),
            ], 201);
        }

        return back()->with('success', 'Vehicle successfully added.');
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:1990|max:2030',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number,' . $vehicle->id,
            'vin' => 'nullable|string|max:50',
            'fuel_type' => 'nullable|string|max:50',
            'tank_capacity' => 'nullable|string|max:20',
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'second_driver_id' => ['nullable', 'exists:drivers,id', 'different:driver_id'],
            'location' => 'nullable|string|max:150',
            'status' => 'required|in:active,maintenance,inactive,disposed',
            'acquisition_date' => 'required|date',
            'exp_disposal_date' => 'nullable|date|after:acquisition_date',
        ]);

        $driverIds = $this->driverIds($validated);
        $this->assertDriversAssignable($validated['status'], $driverIds, $vehicle);
        $this->assertNotDisposedWhileInUse($vehicle, $validated['status']);
        $this->assertServiceNotOverdue($vehicle, $validated['status']);

        // Postcondition: a unit taken out of service loses its drivers.
        if (! in_array($validated['status'], self::ASSIGNABLE_STATUSES, true)) {
            $driverIds = [];
        }

        $vehicle->update(Arr::except($validated, ['driver_id', 'second_driver_id']));
        $this->syncDrivers($vehicle, $driverIds);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Vehicle successfully updated.',
                'vehicle' => $this->formatVehicle($vehicle->load('drivers')),
            ]);
        }

        return back()->with('success', 'Vehicle successfully updated.');
    }

    public function destroy(Request $request, Vehicle $vehicle)
    {

        // A vehicle that still has a driver cannot be removed: unassign them first.
        if ($vehicle->drivers()->exists()) {
            $message = 'This vehicle is assigned to a driver and cannot be deleted. Unassign the drivers first.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 409);
            }

            return back()->with('error', $message);
        }

        // Postcondition: a unit that has a service history is marked Disposed
        // instead of being erased, so its maintenance records stay auditable.
        if ($vehicle->preventiveMaintenances()->exists()) {
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

    /** The (at most two) driver ids from the form, without blanks or repeats. */
    private function driverIds(array $validated): array
    {
        return collect([$validated['driver_id'] ?? null, $validated['second_driver_id'] ?? null])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * E4 — Assigning an out-of-service vehicle: drivers may only be assigned to an active unit; and
     * each must be able to drive, and hold only this one vehicle.
     */
    private function assertDriversAssignable(?string $status, array $driverIds, ?Vehicle $vehicle = null): void
    {
        $current = $vehicle ? $vehicle->drivers()->pluck('drivers.id')->all() : [];

        foreach ($driverIds as $i => $driverId) {
            $field = $i === 0 ? 'driver_id' : 'second_driver_id';

            // Keeping a current driver while taking the unit out of service is allowed — they are
            // simply detached once the status is saved.
            if (in_array($driverId, $current, true)) {
                continue;
            }

            if (! in_array($status, self::ASSIGNABLE_STATUSES, true)) {
                throw ValidationException::withMessages([
                    $field => 'A driver cannot be assigned to a vehicle with a '.strtolower((string) $status).' status.',
                ]);
            }

            $driver = Driver::find($driverId);

            if ($driver && (! $driver->is_approved || $driver->is_rejected || $driver->isSuspended())) {
                throw ValidationException::withMessages([
                    $field => 'Only an approved driver who is not suspended can be assigned to a vehicle.',
                ]);
            }

            // One vehicle per driver.
            $held = DB::table('driver_vehicle')
                ->where('driver_id', $driverId)
                ->when($vehicle, fn ($q) => $q->where('vehicle_id', '!=', $vehicle->id))
                ->exists();

            if ($held) {
                throw ValidationException::withMessages([
                    $field => 'That driver is already assigned to another vehicle.',
                ]);
            }
        }
    }

    /**
     * Make the vehicle's drivers exactly these, and keep its duty roster in step: a driver who leaves
     * drops off the roster from today on, and a vehicle left with one driver needs no roster.
     */
    private function syncDrivers(Vehicle $vehicle, array $driverIds): void
    {
        $before = array_map('intval', $vehicle->drivers()->pluck('drivers.id')->all());
        $after = array_map('intval', $driverIds);
        sort($before);
        sort($after);

        if ($before === $after) {
            return;
        }

        $vehicle->drivers()->sync($driverIds);

        $future = VehicleDutyDay::where('vehicle_id', $vehicle->id)->whereDate('duty_date', '>=', today());

        if (count($after) < Vehicle::MAX_DRIVERS) {
            (clone $future)->delete();
        } else {
            (clone $future)->whereNotIn('driver_id', $after)->delete();
        }

        Audit::log(Audit::FLEET, 'drivers_assigned', 'Set the drivers of vehicle '.($vehicle->plate_number ?: '#'.$vehicle->id), $vehicle, [
            'drivers_before' => $before,
            'drivers_after' => $after,
        ]);
    }

    /**
     * The phone scan this form was filled from, if any. It must be the signed-in person's own, finished, unexpired scan,
     * and they must have ticked "I checked these details against the document".
     */
    private function resolveScan(Request $request): ?VehicleScanSession
    {
        if (! $request->filled('scan_session_id')) {
            return null;
        }

        $session = VehicleScanSession::where('id', $request->input('scan_session_id'))->where('user_id', $request->user()->id)->first();

        if (! $session || $session->visibleStatus() !== VehicleScanSession::READY) {
            throw ValidationException::withMessages(['scan_session_id' => 'That scan is no longer available. Scan the papers again, or fill the form in by hand.']);
        }

        if (! $request->boolean('scan_confirmed')) {
            throw ValidationException::withMessages(['scan_confirmed' => 'Confirm that you checked the details against the document before saving.']);
        }

        return $session;
    }

    /** The vehicle is saved: the photos are deleted, and the audit trail records which values the manager corrected. */
    private function consumeScan(VehicleScanSession $session, Vehicle $vehicle, array $saved): void
    {
        $norm = fn ($v) => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $v));
        $read = $session->result['fields'] ?? [];

        $corrected = collect($read)
            ->filter(fn ($field, $name) => array_key_exists($name, $saved) && $norm($saved[$name]) !== $norm($field['value']))
            ->keys()->values()->all();

        $session->deleteFiles();
        $session->update(['status' => VehicleScanSession::CONSUMED, 'consumed_at' => now(), 'vehicle_id' => $vehicle->id, 'result' => null]);

        Audit::log(Audit::FLEET, 'vehicle_scan_used', 'Added vehicle '.$vehicle->plate_number.' from scanned registration papers', $vehicle, [
            'fields_read' => array_keys($read),
            'fields_corrected_by_manager' => $corrected,
            'needed_a_second_look' => collect($read)->where('status', 'check')->keys()->values()->all(),
        ]);
    }

    /**
     * With MAINTENANCE_BLOCK_ACTIVE_WHEN_OVERDUE on, a unit that is not in service cannot go back to
     * Active while a preventive-maintenance service is overdue: log the service first.
     */
    private function assertServiceNotOverdue(Vehicle $vehicle, ?string $newStatus): void
    {
        if (! config('maintenance.block_active_when_overdue') || $newStatus !== 'active' || $vehicle->status === 'active') {
            return;
        }

        $overdue = app(MaintenanceDueService::class)->forVehicle($vehicle)['overdue'];

        if ($overdue->isNotEmpty()) {
            throw ValidationException::withMessages([
                'status' => 'This vehicle cannot go active while a service is overdue ('.$overdue->pluck('task')->join(', ').'). Log the service first.',
            ]);
        }
    }

    /**
     * E3 — Cannot dispose active vehicle: the assigned driver must not still be
     * clocked into the unit.
     */
    private function assertNotDisposedWhileInUse(Vehicle $vehicle, ?string $newStatus): void
    {
        if ($newStatus !== 'disposed' || ! $vehicle->drivers()->exists()) {
            return;
        }

        $onShift = TimeKeeping::whereIn('driver_id', $vehicle->drivers()->pluck('drivers.id'))
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
