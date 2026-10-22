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
use App\Support\Audit;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DriverController extends Controller
{
    private const NO_VEHICLE_MESSAGE = 'You have no vehicle assigned yet, so you cannot clock in or set your status to active. Please contact the Maintenance Manager.';

    public function create()
    {

        return view('auth.driver.register');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'contact_info' => ['required', 'string', 'max:255', 'regex:/^(?:\+?63|0)?\s?9\d{2}\s?\d{3}\s?\d{4}$/'],
            'password' => 'required|string|min:8',
            'confirm-password' => 'required|same:password',
            'terms' => 'accepted',
            'license_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'password.min' => 'The password field must be at least 8 characters.',
            'contact_info.regex' => 'Enter a valid Philippine mobile number (e.g. 917 123 4567).',
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
            \Illuminate\Support\Facades\Log::error('Error during driver registration.', ['area' => 'Store', 'exception' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'email' => 'A system error occurred while creating your driver account. Please try again later.',
            ]);
        }

        event(new Registered($user));

        Audit::log(Audit::DRIVER, 'registered', 'Driver application submitted by '.$user->email, $user->driver()->first(), [
            'email' => $user->email,
            'status' => 'pending approval',
            'source' => 'self-registration',
        ], $user);

        return redirect()
            ->route('login')
            ->with('driver_registered', true);
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

        // UCN_SC_E023 E1 — no linked Driver record.
        if (! $driver) {
            return redirect()->route('map')->with('error', 'Driver profile not found.');
        }

        try {
            return $this->buildTimekeepingView($request, $driver);
        } catch (QueryException $e) {
            // UCN_SC_E023 E3 — log it and show no data.
            report($e);
            \Illuminate\Support\Facades\Log::error('Database error while loading time-keeping.', ['area' => 'Timekeeping', 'exception' => $e->getMessage()]);

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
        $driver = Driver::where('user_id', Auth::id())->first();

        if (! $driver) {
            return back()->with('error', 'Driver profile not found.');
        }

        // UCN_SC_E012 — a suspended driver may not start a shift.
        if ($driver->isSuspended()) {
            return back()->with('error', 'Your account is suspended. Please contact the administrator.');
        }

        if (! $driver->hasAssignedVehicle()) {
            return back()->with('error', self::NO_VEHICLE_MESSAGE);
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
            \Illuminate\Support\Facades\Log::error('Database error during clock-in.', ['area' => 'Clockin', 'exception' => $e->getMessage()]);

            return back()->with('error', 'Clock-in failed. Please try again later.');
        }

        $driver->update(['status' => 'active']);

        return back()->with('success', 'Clocked in at ' . now()->timezone('Asia/Manila')->format('h:i A') . '.');
    }

    public function clockOut(Request $request)
    {
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
            \Illuminate\Support\Facades\Log::error('Database error during clock-out.', ['area' => 'Clockout', 'exception' => $e->getMessage()]);

            return back()->with('error', 'Clock-out failed. Please try again later.');
        }

        // Ending the shift also takes the driver offline, so they are no longer
        // advertised as available to commuters once they clock out.
        $driver->update(['status' => 'inactive']);

        return back()->with('success', 'Clocked out at ' . $timeOut->timezone('Asia/Manila')->format('h:i A') . '. Total: ' . $hoursWorked . ' hrs.');
    }

    public function updateStatus(Request $request)
    {
        $request->validate(['status' => 'required|in:active,inactive'], [
            'status.required' => 'The status field must be either active or inactive.',
            'status.in' => 'The status field must be either active or inactive.',
        ]);
        $userId = Auth::user()->id;
        $driver = Driver::where('user_id', $userId)->first();

        if (! $driver) {
            return back()->with('error', 'Driver not found.');
        }

        // Going active means being on the road, which needs a vehicle. Going inactive never does.
        if ($request->status === 'active' && ! $driver->hasAssignedVehicle()) {
            return back()->with('error', self::NO_VEHICLE_MESSAGE);
        }

        $driver->update(['status' => $request->status]);

        return back()->with('success', 'You have successfully set your driver status');
    }

    public function violations()
    {
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
            \Illuminate\Support\Facades\Log::error('Database error while loading violations.', ['area' => 'Violations', 'exception' => $e->getMessage()]);

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
                    'paymentStatus' => $v->isPaid() ? ViolationLog::PAID : ViolationLog::UNPAID,
                    'outstanding' => $v->outstandingFine(),
                    'paidAt' => $v->paid_at?->format('d M, Y h:i A'),
                    'penalty' => $v->additional_penalties,
                    'penaltyColor' => $this->getPenaltyColor($v->additional_penalties),
                ];
            });

        // What is still owed: a fine marked paid no longer counts.
        $totalFines = $violations->sum('outstanding');
        $paidFines = $violations->sum(fn ($v) => $v['paymentStatus'] === ViolationLog::PAID ? $v['fine'] : 0);
        $totalViolations = $violations->count();
        $latestViolation = $violations->first();

        // The record is paged 10 at a time; the totals above describe all of it.
        $page = LengthAwarePaginator::resolveCurrentPage();
        $violations = new LengthAwarePaginator(
            $violations->forPage($page, 10)->values(),
            $totalViolations,
            10,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return view('driver.violations', compact('violations', 'totalFines', 'paidFines', 'totalViolations', 'latestViolation'));
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
