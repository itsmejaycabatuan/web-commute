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
    public function create()
    {
        return view('auth.driver.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'contact_info' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'confirm-password' => 'required|same:password',
            'terms' => 'required',
            'license_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $path = $request->file('license_image')->store('licenses', 'public');

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        $driver = $user->driver()->create([
            'contact_info' => $request->contact_info,
            'license_image_path' => $path,
            'license_image_data' => null,
            'license_image_mime' => null,
        ]);

        if (! ($user && $driver)) {
            return back()->with('error', 'Driver registration failed.');
        }

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

        return view('driver.dashboard', [
            'total_distance' => $totalDistance,
        ]);
    }
}
