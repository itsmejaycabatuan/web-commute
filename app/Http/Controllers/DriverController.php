<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VehicleLocationHistory;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class DriverController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'confirm-password' => 'required|same:password',
            'terms' => 'required',
            'license_number' => 'required|string|max:255',
            'license_code' => 'required|string|max:255',
            'license_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $path = $request->file('license_image')->store('licenses', 'public');

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'license_number' => $request->license_number,
            'license_code' => $request->license_code,
            'license_image_path' => $path,
            'license_image_data' => null,
            'license_image_mime' => null,
            'driver_approval_status' => 'pending',
        ]);

        $user->assignRole('driver');
        event(new Registered($user));

        return redirect()
            ->route('login')
            ->with('success', 'Driver registration submitted. Please wait for admin approval before signing in.');
    }

    public function index()
    {
        $userId = Auth::user()->id;

        $distance = VehicleLocationHistory::where('user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->sum('distance_from_last_pos');

        $totalDistance = number_format($distance, 1);

        return view('driverdashboard', [
            'total_distance' => $totalDistance,
        ]);
    }
}
