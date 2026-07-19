<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\FleetInventory;
use App\Models\MaintenanceTask;
use App\Models\User;
use App\Models\Vehicle;
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
        $vehicles = Vehicle::orderBy('brand')->get();

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
        $vehicle = Vehicle::find($selectedId) ?? $vehicles->first();

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
        $log->delete();

        return back()->with('success', 'Maintenance log deleted.');
    }

    public function fleetLog(Request $request)
    {
        $vehicles = Vehicle::with('driver')->orderBy('brand')->get();
        $drivers = Driver::orderBy('name')->get();

        if ($vehicles->isEmpty()) {
            return view('maintenance-manager.fleet-maintenance-log', [
                'vehicles' => collect(),
                'drivers' => $drivers,
                'monthlyMileage' => array_fill(1, 12, 0),
                'monthlyStartOdo' => array_fill(1, 12, null),
                'monthlyEndOdo' => array_fill(1, 12, null),
                'yearStartOdo' => null,
                'yearEndOdo' => 0,
                'vehicle' => null,
                'costSummary' => collect(),
                'monthlyTotals' => array_fill(1, 12, 0),
                'ytdTotal' => 0,
                'allLogs' => collect(),
                'totalServiceCost' => 0,
                'costPerMile' => 0,
                'annualMileage' => 0,
                'year' => now()->year,
                'monthlyCpm' => array_fill(1, 12, null),
            ]);
        }

        $selectedId = $request->query('vehicle_id', $vehicles->first()->id);
        $vehicle = Vehicle::with('driver')->find($selectedId) ?? $vehicles->first();

        $year = now()->year;

        // Logs for current year — used for cost summary table
        $yearLogs = VehicleMaintenanceLog::where('vehicle_id', $vehicle->id)
            ->with('maintenanceTask')
            ->whereYear('service_date', $year)
            ->whereNotNull('service_date')
            ->orderBy('service_date')
            ->get();

        // Build cost summary: task_name => [month => cost]
        $costSummary = [];
        $monthlyTotals = array_fill(1, 12, 0);
        $ytdTotal = 0;

        foreach ($yearLogs as $log) {
            $taskName = $log->maintenanceTask?->tasks_performed ?? 'Unknown Task';
            $month = $log->service_date->month;
            $cost = (float) $log->cost;

            if (! isset($costSummary[$taskName])) {
                $costSummary[$taskName] = array_fill(1, 12, 0);
            }

            $costSummary[$taskName][$month] += $cost;
            $monthlyTotals[$month] += $cost;
            $ytdTotal += $cost;
        }

        ksort($costSummary);
        $costSummary = collect($costSummary);

        // All logs for the log tab (all time, newest first)
        $allLogs = VehicleMaintenanceLog::where('vehicle_id', $vehicle->id)
            ->with('maintenanceTask')
            ->orderByDesc('service_date')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'task_name' => $log->maintenanceTask?->tasks_performed ?? 'Unknown Task',
                    'service_date' => $log->service_date?->format('M d, Y'),
                    'mileage' => $log->mileage_at_service,
                    'cost' => $log->cost,
                    'performed_by' => $log->performed_by,
                    'invoice_number' => $log->invoice_number,
                    'remarks' => $log->remarks,
                ];
            });

        // Stats
        $totalServiceCost = $ytdTotal;

        // Monthly mileage calculation (matches Excel fallback to annual baseline)
        $allOrderedLogs = VehicleMaintenanceLog::where('vehicle_id', $vehicle->id)
            ->whereNotNull('mileage_at_service')
            ->whereNotNull('service_date')
            ->orderBy('service_date')
            ->get();

        $monthlyMileage = array_fill(1, 12, 0);
        $monthlyStartOdo = array_fill(1, 12, null);
        $monthlyEndOdo = array_fill(1, 12, null);
        $monthlyCpm = array_fill(1, 12, null);
        $runningOdo = null;

        // Find annual starting baseline: the odometer reading before the first log of this year
        $firstLogOfYear = $allOrderedLogs->first(fn($l) => $l->service_date && $l->service_date->year === $year);
        $annualStartingOdo = 0;
        if ($firstLogOfYear) {
            $prevLog = $allOrderedLogs->where('id', '<', $firstLogOfYear->id)->last();
            $annualStartingOdo = $prevLog ? $prevLog->mileage_at_service : 0;
        }

        foreach ($allOrderedLogs as $log) {
            $m = $log->service_date->month;
            $monthlyStartOdo[$m] ??= $runningOdo;
            $monthlyEndOdo[$m] = $log->mileage_at_service;

            // Use previous month's ending, or annual starting baseline as fallback
            $baseline = $monthlyStartOdo[$m] ?? $annualStartingOdo;

            if ($baseline !== null) {
                $delta = $log->mileage_at_service - $baseline;
                if ($delta > 0) {
                    $monthlyMileage[$m] += $delta;
                }
            }
            $runningOdo = $log->mileage_at_service;
        }

        $yearStartOdo = $monthlyStartOdo[1];
        $yearEndOdo = $runningOdo;
        $annualMileage = array_sum($monthlyMileage);

        // Pre-calculate cost per mile per month
        for ($m = 1; $m <= 12; $m++) {
            if ($monthlyMileage[$m] > 0) {
                $monthlyCpm[$m] = round($monthlyTotals[$m] / $monthlyMileage[$m], 2);
            }
        }

        $costPerMile = $annualMileage > 0 ? round($totalServiceCost / $annualMileage, 2) : 0;

        return view('maintenance-manager.fleet-maintenance-log', compact(
            'vehicles',
            'drivers',
            'vehicle',
            'costSummary',
            'monthlyTotals',
            'ytdTotal',
            'allLogs',
            'totalServiceCost',
            'costPerMile',
            'annualMileage',
            'year',
            'monthlyMileage',
            'monthlyStartOdo',
            'monthlyEndOdo',
            'yearStartOdo',
            'yearEndOdo',
            'monthlyCpm',
        ));
    }

    public function fleetInventory()
    {
        $inventories = FleetInventory::with('vehicle.driver')->latest()->get()->map(function ($inv) {
            $v = $inv->vehicle;

            return [
                'id' => $inv->id,
                'vehicle_id' => $inv->vehicle_id,
                'maintenance_cost' => $inv->maintenance_cost,
                'notes' => $inv->notes,
                // From the linked vehicle
                'vehicle_name' => $v ? "{$v->brand} {$v->model}" : 'Deleted Vehicle',
                'vehicle_year' => $v?->year,
                'plate_number' => $v?->plate_number,
                'driver_name' => $v?->driver?->name,
                'vehicle_status' => $v?->status,
                // ISO strings for timestamps
                'created_at' => $inv->created_at?->toISOString(),
                'updated_at' => $inv->updated_at?->toISOString(),
            ];
        });

        $vehicles = Vehicle::orderBy('brand')->get();

        return view('maintenance-manager.fleet-inventory', compact('inventories', 'vehicles'));
    }

    public function fleetInventoryStore(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'maintenance_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        FleetInventory::create($validated);

        return back()->with('success', 'Inventory record successfully added.');
    }

    public function fleetInventoryUpdate(Request $request, FleetInventory $fleetInventory)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'maintenance_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $fleetInventory->update($validated);

        return back()->with('success', 'Inventory record successfully updated.');
    }

    public function fleetInventoryDelete(FleetInventory $fleetInventory)
    {
        $fleetInventory->delete();

        return back()->with('success', 'Inventory record successfully deleted.');
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
