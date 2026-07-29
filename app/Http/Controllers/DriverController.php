<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\TimeKeeping;
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

    public function timekeeping(Request $request)
    {
        $driver = Driver::where('user_id', Auth::id())->first();

        // Determine which week to show
        if ($request->filled('week')) {
            $weekStart = Carbon::parse($request->week)->startOfWeek();
        } else {
            $weekStart = now()->startOfWeek();
        }
        $weekEnd = $weekStart->copy()->endOfWeek();
        $isCurrentWeek = $weekStart->eq(now()->startOfWeek());

        // Today's record
        $todayRecord = TimeKeeping::where('driver_id', $driver->id)
            ->whereDate('date', today())
            ->first();

        // This week's records
        $weekRecords = TimeKeeping::where('driver_id', $driver->id)
            ->whereBetween('date', [$weekStart, $weekEnd])
            ->orderBy('date')
            ->get();

        // Stats
        $weekHours = $weekRecords->sum('hours_worked');
        $weekOvertime = $weekRecords->sum('overtime_hours');
        $workingDays = $weekRecords->where('hours_worked', '>', 0)->count();
        $dailyAvg = $workingDays > 0 ? round($weekHours / $workingDays, 1) : 0;

        // Week navigation
        $prevWeek = $weekStart->copy()->subWeek()->format('Y-m-d');
        $nextWeek = $weekStart->copy()->addWeek()->format('Y-m-d');
        $weekLabel = $weekStart->format('M j') . ' – ' . $weekEnd->format('M j, Y');

        return view('driver.time-keeping', [
            'driver' => $driver,
            'todayRecord' => $todayRecord,
            'weekRecords' => $weekRecords,
            'weekHours' => $weekHours,
            'weekOvertime' => $weekOvertime,
            'dailyAvg' => $dailyAvg,
            'workingDays' => $workingDays,
            'isCurrentWeek' => $isCurrentWeek,
            'prevWeek' => $prevWeek,
            'nextWeek' => $nextWeek,
            'weekLabel' => $weekLabel,
            'weekStart' => $weekStart,
        ]);
    }

    public function clockIn(Request $request)
    {
        $driver = Driver::where('user_id', Auth::id())->first();

        $exists = TimeKeeping::where('driver_id', $driver->id)
            ->whereDate('date', today())
            ->exists();

        if ($exists) {
            return back()->with('error', 'You have already clocked in today.');
        }

        TimeKeeping::create([
            'driver_id' => $driver->id,
            'date' => today()->toDateString(),
            'time_in' => now()->toTimeString(),
        ]);

        return back()->with('success', 'Clocked in at ' . now()->format('h:i A') . '.');
    }

    public function clockOut(Request $request)
    {
        $driver = Driver::where('user_id', Auth::id())->first();

        $record = TimeKeeping::where('driver_id', $driver->id)
            ->whereDate('date', today())
            ->whereNull('time_out')
            ->first();

        if (! $record) {
            return back()->with('error', 'No active shift found to clock out.');
        }

        $timeOut = now();
        $timeIn = Carbon::parse($record->date . ' ' . $record->time_in);
        $totalMinutes = $timeIn->diffInMinutes($timeOut);
        $hoursWorked = round($totalMinutes / 60, 2);

        $regularHours = min($hoursWorked, 8);
        $overtimeHours = $hoursWorked > 8 ? round($hoursWorked - 8, 2) : 0;

        $record->update([
            'time_out' => $timeOut->toTimeString(),
            'hours_worked' => $hoursWorked,
            'overtime_hours' => $overtimeHours,
        ]);

        return back()->with('success', 'Clocked out at ' . $timeOut->format('h:i A') . '. Total: ' . $hoursWorked . ' hrs.');
    }
}
