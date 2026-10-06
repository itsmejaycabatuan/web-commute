<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\MaintenanceTask;
use App\Models\PreventiveMaintenance;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleMaintenanceLog;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MaintenanceManagerController extends Controller
{
    public function preventiveMaintenance(Request $request)
    {
        activity()->event('Preventivemaintenance')->log('Action performed: preventiveMaintenance');
        $vehicles = Vehicle::with('driver')
            ->orderBy('plate_number')
            ->get();

        if ($vehicles->isEmpty()) {
            return view('maintenance-manager.preventive-maintenance', [
                'vehicles' => collect(),
                'vehicle' => null,
                'allTasks' => collect(),
                'loggedTasks' => collect(),
            ]);
        }

        $selectedId = $request->query('vehicle_id', $vehicles->first()->id);
        $vehicle = Vehicle::with('driver')->find($selectedId) ?? $vehicles->first();

        $allTasks = MaintenanceTask::orderBy('tasks_performed')->get();

        $loggedTasks = PreventiveMaintenance::where('vehicle_id', $vehicle->id)
            ->get()
            ->keyBy('task_id');

        return view('maintenance-manager.preventive-maintenance', compact(
            'vehicles',
            'vehicle',
            'allTasks',
            'loggedTasks'
        ));
    }

    public function preventiveMaintenanceStore(Request $request)
    {
        activity()->event('Preventivemaintenancestore')->log('Action performed: preventiveMaintenanceStore');
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'task_id' => 'required|exists:maintenance_tasks,id',
            'last_service_odo' => 'required|integer|min:0',
            'last_service_date' => 'required|date',
            'last_service_cost' => 'required|numeric|min:0',
            'comments' => 'nullable|string|max:500',
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

        $maintenance = PreventiveMaintenance::updateOrCreate(
            [
                'vehicle_id' => $validated['vehicle_id'],
                'task_id' => $validated['task_id'],
            ],
            $validated
        );

        // Logging a scheduled service also writes the service-log record the
        // fleet cost/kilometre analytics read.
        VehicleMaintenanceLog::create([
            'maintenance_id' => $maintenance->id,
            'vehicle_id' => $validated['vehicle_id'],
            'maintenance_task_id' => $validated['task_id'],
            'service_date' => $validated['last_service_date'],
            'mileage_at_service' => (int) $validated['last_service_odo'],
            'performed_by' => 'Preventive Maintenance',
            'cost' => $validated['last_service_cost'],
            'remarks' => $validated['comments'] ?? null,
        ]);

        return back()->with('success', 'Preventive maintenance logged successfully!');
    }

    public function maintenanceLogs()
    {
        activity()->event('Maintenancelogs')->log('Action performed: maintenanceLogs');
        $vehicleOptions = Vehicle::orderBy('plate_number')
            ->get()
            ->mapWithKeys(fn($v) => [
                $v->id => $v->plate_number . ' — ' . $v->brand . ' ' . $v->model,
            ])
            ->toArray();

        $logs = VehicleMaintenanceLog::with(['vehicle', 'maintenanceTask'])
            ->when(request('vehicle'), fn ($q, $vehicle) => $q->where('vehicle_id', $vehicle))
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('maintenance-manager.maintenance-logs', compact('logs', 'vehicleOptions'));
    }

    public function maintenanceTasks()
    {
        activity()->event('Maintenancetasks')->log('Action performed: maintenanceTasks');
        $tasks = MaintenanceTask::all();

        return view('maintenance-manager.maintenance-tasks', compact('tasks'));
    }

    public function maintenanceTasksStore(Request $request)
    {
        activity()->event('Maintenancetasksstore')->log('Action performed: maintenanceTasksStore');
        $request->validate([
            'tasks_performed' => 'required|string|max:255',
            'miles_between_service' => 'nullable|integer',
            'months_between_service' => 'nullable|integer',
        ]);

        $task = MaintenanceTask::create([
            'tasks_performed' => $request->tasks_performed,
            'miles_between_service' => $request->miles_between_service,
            'months_between_service' => $request->months_between_service,
        ]);

        if (! $task) {
            return back()->with('error', 'Failed to add task');
        }

        return back()->with('success', 'Task successfully added!');
    }

    public function maintenanceTasksUpdate(Request $request, MaintenanceTask $task)
    {
        activity()->event('Maintenancetasksupdate')->log('Action performed: maintenanceTasksUpdate');
        $request->validate([
            'tasks_performed' => 'required|string|max:255',
            'miles_between_service' => 'nullable|integer',
            'months_between_service' => 'nullable|integer',
        ]);

        $task->update([
            'tasks_performed' => $request->tasks_performed,
            'miles_between_service' => $request->miles_between_service,
            'months_between_service' => $request->months_between_service,
        ]);

        return back()->with('success', 'Task successfully updated!');
    }

    public function maintenanceTasksDestroy(MaintenanceTask $task)
    {
        activity()->event('Maintenancetasksdestroy')->log('Action performed: maintenanceTasksDestroy');
        $task->delete();

        return back()->with('success', 'Task successfully deleted!');
    }

    public function vehicleLog(Request $request)
    {
        activity()->event('Vehiclelog')->log('Action performed: vehicleLog');
        $vehicles = Vehicle::with('driver')
            ->orderBy('plate_number')
            ->get();

        if ($vehicles->isEmpty()) {
            return view('maintenance-manager.vehicle-maintenance-log', [
                'vehicles' => collect(),
                'vehicle' => null,
                'maintenanceTasks' => collect(),
                'logs' => collect(),
                'totalCost' => 0,
                'totalServices' => 0,
                'latestOdometer' => 0,
                'costPerMile' => 0,
            ]);
        }

        $selectedId = $request->query('vehicle_id', $vehicles->first()->id);
        $vehicle = $vehicles->firstWhere('id', $selectedId) ?? $vehicles->first();
        $vehicle->load('driver');

        $logs = VehicleMaintenanceLog::where('vehicle_id', $vehicle->id)
            ->with('maintenanceTask')
            ->orderByDesc('service_date')
            ->get();

        $logs->transform(function ($log) {
            $log->service_date_formatted = $log->service_date?->format('Y-m-d');

            return $log;
        });

        $totalCost = $logs->sum('cost');
        $totalServices = $logs->count();
        $latestOdometer = $logs->first()?->mileage_at_service ?? 0;
        $costPerMile = $latestOdometer > 0 ? round($totalCost / $latestOdometer, 2) : 0;

        $maintenanceTasks = MaintenanceTask::orderBy('tasks_performed')->get();

        return view('maintenance-manager.vehicle-maintenance-log', compact(
            'vehicles',
            'vehicle',
            'maintenanceTasks',
            'logs',
            'totalCost',
            'totalServices',
            'latestOdometer',
            'costPerMile'
        ));
    }

    public function vehicleLogStore(Request $request)
    {
        activity()->event('Vehiclelogstore')->log('Action performed: vehicleLogStore');
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'maintenance_task_id' => 'required|exists:maintenance_tasks,id',
            'service_date' => 'required|date',
            'mileage_at_service' => 'required|integer|min:0',
            'performed_by' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'invoice_number' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:500',
        ]);

        VehicleMaintenanceLog::create($validated);

        return back()->with('success', 'Maintenance info logged!');
    }

    public function vehicleLogUpdate(Request $request, VehicleMaintenanceLog $log)
    {
        activity()->event('Vehiclelogupdate')->log('Action performed: vehicleLogUpdate');
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'maintenance_task_id' => 'required|exists:maintenance_tasks,id',
            'service_date' => 'required|date',
            'mileage_at_service' => 'required|integer|min:0',
            'performed_by' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'invoice_number' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:500',
        ]);

        $log->update($validated);

        return back()->with('success', 'Maintenance log updated!');
    }

    public function vehicleLogDelete(VehicleMaintenanceLog $log)
    {
        activity()->event('Vehiclelogdelete')->log('Action performed: vehicleLogDelete');
        $log->delete();

        return back()->with('success', 'Maintenance log deleted.');
    }

    /**
     * The full-page fleet maintenance log: the same summary the maintenance
     * manager dashboard shows, on its own page (UCN_SC_E016 A4).
     */
    public function profile()
    {
        activity()->event('Profile')->log('Action performed: profile');
        $user = Auth::user();

        return view('driver-manager.profile', [
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        activity()->event('Updateprofile')->log('Action performed: updateProfile');
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
