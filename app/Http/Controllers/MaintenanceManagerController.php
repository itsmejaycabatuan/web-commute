<?php

namespace App\Http\Controllers;

use App\Models\User;
use Auth;
use Request;

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
        return view('maintenance-manager.fleet-inventory');
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
