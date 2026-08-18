<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\VehicleLocationHistory;
use App\Models\ViolationLog;
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
            'time_in' => now()->timezone('Asia/Manila')->format('h:i A'),
        ]);

        $driver->update(['status' => 'active']);

        return back()->with('success', 'Clocked in at ' . now()->timezone('Asia/Manila')->format('h:i A') . '.');
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
        $timeIn = Carbon::parse($record->date . ' ' . $record->time_in, 'Asia/Manila');
        $totalMinutes = $timeIn->diffInMinutes($timeOut);
        $hoursWorked = round($totalMinutes / 60, 2);

        $regularHours = min($hoursWorked, 8);
        $overtimeHours = $hoursWorked > 8 ? round($hoursWorked - 8, 2) : 0;

        $record->update([
            'time_out' => $timeOut->timezone('Asia/Manila')->format('h:i A'),
            'hours_worked' => $hoursWorked,
            'overtime_hours' => $overtimeHours,
        ]);

        return back()->with('success', 'Clocked out at ' . $timeOut->timezone('Asia/Manila')->format('h:i A') . '. Total: ' . $hoursWorked . ' hrs.');
    }

    public function updateStatus(Request $request)
    {
        $request->validate(['status' => 'required|in:active,inactive']);
        $userId = Auth::user()->id;
        $driver = Driver::where('user_id', $userId)->first();

        if (! $driver) {
            return back()->with('error', 'Driver not found.');
        }

        $driver->update(['status' => $request->status]);

        return back()->with('success', 'You have successfully set your driver status');
    }

    public function violations()
    {
        $userId = Auth::user()->id;
        $driver = Driver::where('user_id', $userId)->first();

        if (! $driver) {
            abort(403, 'Driver profile not found.');
        }

        $violations = ViolationLog::with('violationCode')
            ->where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'violationType' => $v->violationCode?->violation_name ?? 'Unknown',
                    'violationCode' => $v->violationCode?->code ?? 'N/A',
                    'codeColor' => $v->violationCode?->severity ?? 'amber',
                    'offenseCount' => $v->violation_instance,
                    'remarks' => $v->remarks,
                    'location' => $v->place_of_violation,
                    'date' => Carbon::parse($v->date_of_violation)->format('d F, Y'),
                    'time' => Carbon::parse($v->time_of_violation)->format('h:i A'),
                    'fine' => (float) $v->violation_fine,
                    'penalty' => $v->additional_penalties,
                    'penaltyColor' => $this->getPenaltyColor($v->additional_penalties),
                ];
            });

        $totalFines = $violations->sum('fine');

        return view('driver.violations', compact('violations', 'totalFines'));
    }

    private function getPenaltyColor($penalty)
    {
        if (! $penalty || $penalty === 'N/A') {
            return 'muted';
        }
        $lower = strtolower($penalty);
        if (str_contains($lower, ['suspension', 'revocation', 'impound'])) {
            return 'red';
        }
        if (str_contains($lower, ['warning', 'community'])) {
            return 'amber';
        }

        return 'muted';
    }
}
