<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\MaintenanceTask;
use App\Models\PreventiveMaintenance;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMaintenanceLog;
use Auth;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenanceManagerController extends Controller
{
    public function preventiveMaintenance(Request $request)
    {
        $vehicles = Vehicle::orderBy('plate_number')
            ->get();

        if ($vehicles->isEmpty()) {
            return view('maintenance-manager.preventive-maintenance', [
                'vehicles' => collect(),
                'vehicle' => null,
                'allTasks' => collect(),
                'loggedTasks' => collect(),
                'currentOdo' => null,
            ]);
        }

        $selectedId = $request->query('vehicle_id', $vehicles->first()->id);
        $vehicle = Vehicle::find($selectedId) ?? $vehicles->first();

        $allTasks = MaintenanceTask::orderBy('tasks_performed')->get();

        $loggedTasks = PreventiveMaintenance::with('maintenanceTask')
            ->where('vehicle_id', $vehicle->id)
            ->get()
            ->keyBy('task_id');

        // The latest odometer reading logged for this vehicle: what "due in 500 km" is measured from.
        $currentOdo = $loggedTasks->max('last_service_odo');

        return view('maintenance-manager.preventive-maintenance', compact(
            'vehicles',
            'vehicle',
            'allTasks',
            'loggedTasks',
            'currentOdo'
        ));
    }

    /**
     * The maintenance log: every service ever logged for a vehicle, newest first.
     * Maintenance Manager only (see the route group).
     */
    public function maintenanceLogs(Request $request)
    {
        $vehicles = Vehicle::orderBy('plate_number')->get();
        $vehicle = $vehicles->firstWhere('id', (int) $request->query('vehicle_id')) ?? $vehicles->first();

        $logs = $vehicle
            ? VehicleMaintenanceLog::with('maintenanceTask')
                ->where('vehicle_id', $vehicle->id)
                ->orderByDesc('service_date')
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
            : null;

        return view('maintenance-manager.maintenance-logs', compact('vehicles', 'vehicle', 'logs'));
    }

    public function preventiveMaintenanceStore(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'task_id' => 'required|exists:maintenance_tasks,id',
            'last_service_odo' => 'required|integer|min:0',
            'last_service_date' => 'required|date',
            'last_service_cost' => 'required|numeric|min:0',
            'comments' => 'nullable|string|max:500',
            'performed_by' => 'nullable|string|max:255',
            'invoice_number' => 'nullable|string|max:100',
        ]);

        // E2 — Invalid odometer reading: a reading may never be lower than the
        // last one recorded for this vehicle (across every scheduled service).
        $lastOdo = (int) PreventiveMaintenance::where('vehicle_id', $validated['vehicle_id'])
            ->max('last_service_odo');

        $highest = $lastOdo;

        if ((int) $validated['last_service_odo'] < $highest) {
            throw ValidationException::withMessages([
                'last_service_odo' => 'The odometer reading cannot be lower than the last recorded reading of '.
                    number_format($highest).' km.',
            ]);
        }

        // E4 — Database error: the schedule entry and its log record are written
        // together or not at all, and the Maintenance Manager is told.
        try {
            DB::transaction(function () use ($validated) {
                $maintenance = PreventiveMaintenance::updateOrCreate(
                    [
                        'vehicle_id' => $validated['vehicle_id'],
                        'task_id' => $validated['task_id'],
                    ],
                    collect($validated)->only([
                        'last_service_odo',
                        'last_service_date',
                        'last_service_cost',
                        'comments',
                    ])->all()
                );

                // The schedule only remembers the latest service; the log keeps every one.
                VehicleMaintenanceLog::create([
                    'maintenance_id' => $maintenance->id,
                    'vehicle_id' => $validated['vehicle_id'],
                    'maintenance_task_id' => $validated['task_id'],
                    'service_date' => $validated['last_service_date'],
                    'mileage_at_service' => (int) $validated['last_service_odo'],
                    'performed_by' => $validated['performed_by'] ?? null,
                    'cost' => $validated['last_service_cost'],
                    'invoice_number' => $validated['invoice_number'] ?? null,
                    'remarks' => $validated['comments'] ?? null,
                ]);
            });
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The scheduled service was not saved.');
        }

        return back()->with('success', 'Preventive maintenance logged successfully!');
    }

    /**
     * UCN_SC_E016 E4 / E017 E3 — a failed write is logged, cancelled
     * and reported back to the Maintenance Manager instead of a raw 500 page.
     */
    private function writeFailed(QueryException $e, string $detail)
    {
        report($e);

        return back()
            ->withInput()
            ->with('error', 'A database error occurred. '.$detail);
    }

    public function maintenanceTasks()
    {
        $tasks = MaintenanceTask::orderBy('tasks_performed')->paginate(10)->withQueryString();

        // The summary cards describe the whole catalogue, not just this page.
        $stats = [
            'total' => MaintenanceTask::count(),
            'withMiles' => MaintenanceTask::where('miles_between_service', '>', 0)->count(),
            'withMonths' => MaintenanceTask::where('months_between_service', '>', 0)->count(),
        ];

        return view('maintenance-manager.maintenance-tasks', compact('tasks', 'stats'));
    }

    public function maintenanceTasksStore(Request $request)
    {
        $request->validate([
            'tasks_performed' => 'required|string|max:255',
            'miles_between_service' => 'nullable|integer',
            'months_between_service' => 'nullable|integer',
        ]);

        // E3 — Database error: nothing is stored and the Maintenance Manager is told.
        try {
            $task = MaintenanceTask::create([
                'tasks_performed' => $request->tasks_performed,
                'miles_between_service' => $request->miles_between_service,
                'months_between_service' => $request->months_between_service,
            ]);
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The task was not added.');
        }

        if (! $task) {
            return back()->with('error', 'Failed to add task');
        }

        return back()->with('success', 'Task successfully added!');
    }

    public function maintenanceTasksUpdate(Request $request, MaintenanceTask $task)
    {
        $request->validate([
            'tasks_performed' => 'required|string|max:255',
            'miles_between_service' => 'nullable|integer',
            'months_between_service' => 'nullable|integer',
        ]);

        try {
            $task->update([
                'tasks_performed' => $request->tasks_performed,
                'miles_between_service' => $request->miles_between_service,
                'months_between_service' => $request->months_between_service,
            ]);
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The task was not updated.');
        }

        return back()->with('success', 'Task successfully updated!');
    }

    public function maintenanceTasksDestroy(MaintenanceTask $task)
    {
        try {
            $task->delete();
        } catch (QueryException $e) {
            return $this->writeFailed($e, 'The task was not deleted.');
        }

        return back()->with('success', 'Task successfully deleted!');
    }

    public function profile()
    {
        $user = Auth::user();

        return view('driver-manager.profile', [
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
        ]);

        $userId = Auth::user()->id;
        $user = User::where('id', $userId)->first();

        if ($user->update(['password' => $request->password_confirmation])) {
            return redirect()->route('commuter.profile')->with('success', 'password successfully updated');
        }

        return back()->with('error', 'Failed to update password');
    }
}
