<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\TimeKeeping;
use App\Models\User;
use App\Models\VehicleLocationHistory;
use App\Models\ViolationLog;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DriverController extends Controller
{
    public function create()
    {
        activity()->event('Create')->log('Action performed: create');

        return view('auth.driver.register');
    }

    public function store(Request $request)
    {
        activity()->event('Store')->log('Action performed: store');
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'contact_info' => 'required|string|max:255',
            'password' => 'required|string|min:8',
            'confirm-password' => 'required|same:password',
            'terms' => 'accepted',
            'license_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'password.min' => 'The password field must be at least 8 characters.',
            'confirm-password.same' => 'The password confirmation does not match.',
            'license_image.mimes' => 'The license image must be an image of type jpg, jpeg, png.',
        ]);

        // UCN_SC_E022 E7/E8 — the licence image, the user and the driver record
        // are created together or not at all. A storage failure is treated the
        // same as a database failure, and a stored image is removed again when
        // the records cannot be written.
        $path = null;

        try {
            $path = $request->file('license_image')->store('licenses', 'public');

            if (! $path) {
                throw new \RuntimeException('The license image could not be written to the public disk.');
            }

            $user = DB::transaction(function () use ($request, $path) {
                $user = User::create([
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'email_verified_at' => now(),
                ]);

                $user->driver()->create([
                    'name' => $request->name,
                    'contact_info' => $request->contact_info,
                    'license_image_path' => $path,
                    'license_image_data' => null,
                    'license_image_mime' => null,
                ]);

                $user->assignRole('driver');

                return $user;
            });
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }

            report($e);
            activity()->event('Store')->log('Error during driver registration.');

            throw ValidationException::withMessages([
                'email' => 'A system error occurred while creating your driver account. Please try again later.',
            ]);
        }

        event(new Registered($user));

        return redirect()
            ->route('login')
            ->with('driver_registered', true);
    }

    public function index()
    {
        activity()->event('Index')->log('Action performed: index');
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
        activity()->event('Timekeeping')->log('Action performed: timekeeping');
        $driver = Driver::where('user_id', Auth::id())->first();

        // UCN_SC_E023 E1 — no linked Driver record.
        if (! $driver) {
            return redirect()->route('map')->with('error', 'Driver profile not found.');
        }

        try {
            return $this->buildTimekeepingView($request, $driver);
        } catch (QueryException $e) {
            // UCN_SC_E023 E3 — log it and show no data.
            report($e);
            activity()->event('Timekeeping')->log('Database error while loading time-keeping.');

            return redirect()->route('map')->with('error', 'Time-keeping failed. Please try again later.');
        }
    }

    private function buildTimekeepingView(Request $request, Driver $driver)
    {
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
        activity()->event('Clockin')->log('Action performed: clockIn');
        $driver = Driver::where('user_id', Auth::id())->first();

        if (! $driver) {
            return back()->with('error', 'Driver profile not found.');
        }

        // UCN_SC_E012 — a suspended driver may not start a shift.
        if ($driver->isSuspended()) {
            return back()->with('error', 'Your account is suspended. Please contact the administrator.');
        }

        $exists = TimeKeeping::where('driver_id', $driver->id)
            ->whereDate('date', today())
            ->exists();

        if ($exists) {
            return back()->with('error', 'You have already clocked in today.');
        }

        try {
            TimeKeeping::create([
                'driver_id' => $driver->id,
                'date' => today()->toDateString(),
                'time_in' => now()->timezone('Asia/Manila')->format('h:i A'),
            ]);
        } catch (\Exception $e) {
            activity()->event('Clockin')->log('Database error during clock-in.');

            return back()->with('error', 'Clock-in failed. Please try again later.');
        }

        $driver->update(['status' => 'active']);

        return back()->with('success', 'Clocked in at ' . now()->timezone('Asia/Manila')->format('h:i A') . '.');
    }

    public function clockOut(Request $request)
    {
        activity()->event('Clockout')->log('Action performed: clockOut');
        $driver = Driver::where('user_id', Auth::id())->first();

        if (! $driver) {
            return back()->with('error', 'Driver profile not found.');
        }

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

        try {
            $record->update([
                'time_out' => $timeOut->timezone('Asia/Manila')->format('h:i A'),
                'hours_worked' => $hoursWorked,
                'overtime_hours' => $overtimeHours,
            ]);
        } catch (\Exception $e) {
            activity()->event('Clockout')->log('Database error during clock-out.');

            return back()->with('error', 'Clock-out failed. Please try again later.');
        }

        // Ending the shift also takes the driver offline, so they are no longer
        // advertised as available to commuters once they clock out.
        $driver->update(['status' => 'inactive']);

        return back()->with('success', 'Clocked out at ' . $timeOut->timezone('Asia/Manila')->format('h:i A') . '. Total: ' . $hoursWorked . ' hrs.');
    }

    public function updateStatus(Request $request)
    {
        activity()->event('Updatestatus')->log('Action performed: updateStatus');
        $request->validate(['status' => 'required|in:active,inactive'], [
            'status.required' => 'The status field must be either active or inactive.',
            'status.in' => 'The status field must be either active or inactive.',
        ]);
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
        activity()->event('Violations')->log('Action performed: violations');
        $userId = Auth::user()->id;
        $driver = Driver::where('user_id', $userId)->first();

        // UCN_SC_E023 E1 — no linked Driver record.
        if (! $driver) {
            return redirect()->route('map')->with('error', 'Driver profile not found.');
        }

        try {
            $violations = ViolationLog::with('violationCode')
                ->where('user_id', Auth::id())
                ->latest()
                ->get();
        } catch (QueryException $e) {
            // UCN_SC_E023 E3 — log it and show no data.
            report($e);
            activity()->event('Violations')->log('Database error while loading violations.');

            return redirect()->route('map')->with('error', 'Violations failed. Please try again later.');
        }

        $violations = $violations
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
        if (Str::contains($lower, ['suspension', 'impound'])) {
            return 'red';
        }
        if (Str::contains($lower, ['warning', 'community'])) {
            return 'amber';
        }

        return 'muted';
    }
}
