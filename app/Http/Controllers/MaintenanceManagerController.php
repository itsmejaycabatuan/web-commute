<?php

namespace App\Http\Controllers;

use App\Models\FleetInventory;
use App\Models\User;
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
        return view('maintenance-manager.maintenance-tasks');
    }

    public function vehicleLog()
    {
        return view('maintenance-manager.vehicle-maintenance-log');
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
