<?php

namespace App\Http\Controllers;

use App\Models\FleetInventory;
use App\Models\MaintenanceTask;
use App\Models\User;
use App\Models\VehicleMaintenanceLog;
use Auth;
use Illuminate\Http\Request;

class MaintenanceManagerController extends Controller
{
    public function preventiveMaintenance()
    {
        return view('maintenance-manager.preventive-maintenance');
    }

    public function maintenanceCalendar()
    {
        return view('maintenance-manager.maintenance-calendar');
    }

    public function maintenanceTasks()
    {
        $tasks = MaintenanceTask::all();

        return view('maintenance-manager.maintenance-tasks', compact('tasks'));
    }

    public function maintenanceTasksStore(Request $request)
    {
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
        $task->delete();

        return back()->with('success', 'Task successfully deleted!');
    }

    public function vehicleLog(Request $request)
    {
        $vehicles = FleetInventory::orderBy('fleet_id')->get();

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
        $vehicle = FleetInventory::find($selectedId) ?? $vehicles->first();

        // Fetch logs with their related maintenance task
        $logs = VehicleMaintenanceLog::where('fleet_id', $vehicle->id)
            ->with('maintenanceTask')
            ->orderByDesc('service_date')
            ->get();

        $logs->transform(function ($log) {
            // Y-m-d is the exact format HTML date inputs require
            $log->service_date_formatted = $log->service_date->format('Y-m-d');

            return $log;
        });

        // Calculate summary values
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

    public function vehicleLogStore(Request $request, string $id)
    {
        $request->validate([
            'service_date' => 'required|date',
            'mileage_at_service' => 'required|integer',
            'task_id' => 'required',
            'performed_by' => 'required|string',
            'cost' => 'required|integer',
            'invoice_number' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $log = VehicleMaintenanceLog::create([
            'fleet_id' => $request->vehicle_id,
            'maintenance_task_id' => $request->task_id,
            'service_date' => $request->service_date,
            'mileage_at_service' => $request->mileage_at_service,
            'performed_by' => $request->performed_by,
            'cost' => $request->cost,
            'invoice_number' => $request->invoice_number,
            'remarks' => $request->remarks,
        ]);

        if (! $log) {
            return back()->with('error', 'Error logging maintenance info.');
        }

        return back()->with('success', 'Maintenance info logged!');
    }

    public function vehicleLogUpdate(Request $request)
    {
        $log = VehicleMaintenanceLog::findOrFail($request->id);

        $request->validate([
            'service_date' => 'required|date',
            'mileage_at_service' => 'required|integer',
            'task_id' => 'required|exists:maintenance_tasks,id',
            'performed_by' => 'required|string',
            'cost' => 'required',
            'invoice_number' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $log->update([
            'maintenance_task_id' => $request->task_id,
            'service_date' => $request->service_date,
            'mileage_at_service' => $request->mileage_at_service,
            'performed_by' => $request->performed_by,
            'cost' => $request->cost,
            'invoice_number' => $request->invoice_number,
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Maintenance log updated!');
    }

    public function fleetLog()
    {
        return view('maintenance-manager.fleet-maintenance-log');
    }

    public function fleetInventory()
    {
        $inventories = FleetInventory::orderBy('created_at', 'desc')->get();

        return view('maintenance-manager.fleet-inventory', compact('inventories'));
    }

    public function fleetInventoryStore(Request $request)
    {
        $validated = $request->validate([
            'fleet_id' => 'required|string|max:255|unique:fleet_inventories,fleet_id',
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1990|max:2030',
            'engine' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'condition' => 'required|string|max:255',
            'purchase_cost' => 'required|numeric|min:0',
            'maintenance_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $fleetInventory = FleetInventory::create([
            'fleet_id' => $validated['fleet_id'],
            'make' => $validated['make'],
            'model' => $validated['model'],
            'year' => $validated['year'],
            'engine' => $validated['engine'],
            'purchase_date' => $validated['purchase_date'],
            'purchase_cost' => $validated['purchase_cost'],
            'maintenance_cost' => $validated['maintenance_cost'] ?? 0,
            'condition' => $validated['condition'],
            'notes' => $validated['notes'] ?? null,
        ]);

        if (! $fleetInventory) {
            return back()->with('error', 'Failed to add new entry.');
        }

        return redirect()->back()->with('success', 'New Entry Added');
    }

    public function fleetInventoryUpdate(Request $request, string $id)
    {
        $inventory = FleetInventory::findOrFail($id);

        $validated = $request->validate([
            'fleet_id' => 'nullable|string',
            'make' => 'required|string',
            'model' => 'required|string',
            'year' => 'nullable|string',
            'engine' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric',
            'maintenance_cost' => 'nullable|numeric',
            'condition' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $inventory->update($validated);

        return back()->with('sucess', 'Vehicle successfully updated!');
    }

    public function fleetInventoryDelete(string $id)
    {
        FleetInventory::findOrFail($id)->delete();

        return back()->with('success', 'Vehicle deleted successfully');
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
