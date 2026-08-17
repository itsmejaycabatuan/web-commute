<?php

namespace App\Http\Controllers;

use App\Helpers\LocationPrivacy;
use App\Mail\EmailVerification;
use App\Models\DevMarker;
use App\Models\Driver;
use App\Models\Fare;
use App\Models\FareRate;
use App\Models\FleetInventory;
use App\Models\Payment;
use App\Models\PreventiveMaintenance;
use App\Models\TimeKeeping;
use App\Models\TopupHistory;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;
use App\Models\ViolationLog;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function map(Request $request)
    {
        $user = Auth::user();
        $userId = Auth::user()->id;
        $role = $user->roles->first()->name;
        $latestFare = Fare::get()->last();
        $wallet = Wallet::where('user_id', $userId)->first();
        $driverStatus = $request->user()->driver?->status ?? 'inactive';

        $recentReceipts = Payment::where('paid_by', $userId)->latest()->take(3)->get();

        $rates = FareRate::get();

        if ($latestFare) {
            $latestFareId = $latestFare->id;
            $rates = FareRate::where('fare_id', $latestFareId)->get();
        }

        if ($role == 'admin') {
            return view('map', [
                'rates' => $rates,
            ]);
        }

        if ($role == 'maintenance_manager') {
            return view('map', [
                'rates' => $rates,
            ]);
        }

        if ($role == 'driver_manager') {
            return view('map', [
                'rates' => $rates,
            ]);
        }

        if ($role == 'driver') {
            $driver = Driver::where('user_id', $user->id)->first();
            $todayRecord = null;
            $dummyMarkers = app()->environment('local')
               ? DevMarker::where('user_id', Auth::id())->latest()->get()
               : collect();

            $todayRecord = TimeKeeping::where('driver_id', $driver->id)
                ->whereDate('date', today())
                ->first();

            if ($driver->is_approved != true && $driver->is_rejected != true) {
                Auth::logout();

                return redirect()->route('login')->with('driver_pending', true);
            }

            if ($user->is_rejected == true) {
                Auth::logout();

                return redirect()->route('login')->with('driver_rejected', true);
            }

            return view('map', [
                'rates' => $rates,
                'recentReceipts' => $recentReceipts,
                'todayRecord' => $todayRecord,
                'driverStatus' => $driverStatus,
                'dummyMarkers' => $dummyMarkers,
            ]);
        }

        if ($role == 'commuter') {
            $obfuscatedMarkers = collect();
            if (app()->environment('local')) {
                $rawMarkers = DevMarker::where('status', 'active')->get();
                $obfuscatedMarkers = $rawMarkers->map(function ($m) {
                    $private = LocationPrivacy::obfuscate($m->lat, $m->lng);

                    return [
                        'id' => $m->id,
                        'name' => $m->name,
                        'plate_number' => $m->plate_number ?? null,
                        'route' => $m->route ?? null,
                        'status' => $m->status,
                        'lat' => $private['lat'],
                        'lng' => $private['lng'],
                        'privacy_radius' => $private['privacy_radius'],
                    ];
                });
            }

            return view('map', [
                'rates' => $rates,
                'recentReceipts' => $recentReceipts,
                'balance' => $wallet->balance ?? 0.00,
                'obfuscatedMarkers' => $obfuscatedMarkers,
            ]);
        }
    }

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $userId = Auth::user()->id;
        $role = $user->roles->first()->name;
        $latestFare = Fare::get()->last();
        $wallet = Wallet::where('user_id', $userId)->first();

        $recentReceipts = Payment::where('paid_by', $userId)->latest()->take(3)->get();

        $rates = FareRate::get();

        if ($latestFare) {
            $latestFareId = $latestFare->id;
            $rates = FareRate::where('fare_id', $latestFareId)->get();
        }

        $distance = VehicleLocationHistory::where('user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->sum('distance_from_last_pos');

        $totalDistance = number_format($distance, 1);

        if ($role == 'admin') {
            $totalRevenue = Payment::sum('price');
            $totalFundsAdded = TopupHistory::sum('amount_added');
            $activeUsersCount = Payment::distinct('paid_by')->count();
            $recentFares = Payment::with('user')->latest()->take(5)->get();
            $recentTopups = TopupHistory::with('user')->latest()->take(5)->get();
            $revenueByDay = FareTransaction::where('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as date, SUM(price) as total')
                ->groupBy('date')->orderBy('date')->pluck('total', 'date')->toArray();

            $topupsByDay = TopupHistory::where('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as date, SUM(amount_added) as total')
                ->groupBy('date')->orderBy('date')->pluck('total', 'date')->toArray();

            $inactiveUsersCount = User::where('is_active', false)->count();

            return view('admin.dashboard', [
                'totalRevenue' => $totalRevenue,
                'totalFundsAdded' => $totalFundsAdded,
                'activeUsersCount' => $activeUsersCount, // Or your preferred logic
                'recentFares' => $recentFares,
                'recentTopups' => $recentTopups,
                'revenueByDay' => $revenueByDay,
                'topupsByDay' => $topupsByDay,
                'inactiveUsersCount' => $inactiveUsersCount,
            ]);
        }

        // $total_distance comes from however you're tracking it (payments, trips, etc.)
        if ($role == 'driver') {
            $driver = Driver::where('user_id', Auth::id())->first();

            $todayRecord = TimeKeeping::where('driver_id', $driver->id)
                ->whereDate('date', today())
                ->first();

            $recentTimeKeeping = TimeKeeping::where('driver_id', $driver->id)
                ->latest('date')
                ->take(7)
                ->get();

            $weekStart = now()->startOfWeek();
            $weekEnd = now()->endOfWeek();

            $weekHours = TimeKeeping::where('driver_id', $driver->id)
                ->whereBetween('date', [$weekStart, $weekEnd])
                ->sum('hours_worked');

            $weekOvertime = TimeKeeping::where('driver_id', $driver->id)
                ->whereBetween('date', [$weekStart, $weekEnd])
                ->sum('overtime_hours');

            $vehicle = Vehicle::where('driver_id', $driver->id)->first();

            return view('driver.dashboard', [
                'driver' => $driver,
                'todayRecord' => $todayRecord,
                'recentTimeKeeping' => $recentTimeKeeping,
                'weekHours' => $weekHours,
                'weekOvertime' => $weekOvertime,
                'vehicle' => $vehicle,
                'total_distance' => $total_distance ?? 0,
            ]);
        }

        if ($role == 'driver_manager') {
            $drivers = Driver::with('user')->get()->map(fn($d) => [
                'id' => $d->id,
                'user_id' => $d->user_id,
                'name' => $d->name,
                'driver_code' => $d->driver_code ?? 'N/A',
                'license_number' => $d->license_number ?? 'N/A',
                'expiration_date' => $d->expiration_date
                                        ? Carbon::parse($d->expiration_date)->format('F d, Y')
                                        : 'N/A',
            ])->values();

            $timeKeepings = TimeKeeping::with('driver')->get()->map(fn($tk) => [
                'driver_id' => $tk->driver_id,
                'driver_name' => $tk->driver->name ?? 'Unknown',
                'driver_user_id' => $tk->driver->user_id ?? null,
                'date' => (string) $tk->date,
                'time_in' => $tk->time_in ? (string) $tk->time_in : null,
                'time_out' => $tk->time_out ? (string) $tk->time_out : null,
                'hours_worked' => (float) ($tk->hours_worked ?? 0),
                'overtime_hours' => (float) ($tk->overtime_hours ?? 0),
                'sick' => (int) $tk->sick,
                'vacation' => (int) $tk->vacation,
            ])->values();

            $violationLogs = ViolationLog::with('user')->get()->map(fn($v) => [
                'id' => $v->id,
                'user_id' => $v->user_id,
                'user_name' => $v->user->name ?? 'Unknown',
                'violation_instance' => $v->violation_instance,
                'violation_fine' => (float) ($v->violation_fine ?? 0),
                'created_at' => $v->created_at ? $v->created_at->format('M d, Y') : '',
                'time' => $v->created_at ? $v->created_at->format('g:i A') : '',
            ])->values();

            return view('driver-manager.dashboard', compact('drivers', 'timeKeepings', 'violationLogs'));
        }

        if ($role == 'maintenance_manager') {
            $fleets = FleetInventory::with('vehicle.driver')
                ->join('vehicles', 'fleet_inventories.vehicle_id', '=', 'vehicles.id')
                ->orderBy('vehicles.plate_number')
                ->select('fleet_inventories.*')
                ->get();

            $drivers = Driver::orderBy('name')->get();

            if ($fleets->isEmpty()) {
                return view('maintenance-manager.dashboard', [
                    'fleets' => collect(),
                    'drivers' => $drivers,
                    'monthlyKm' => array_fill(1, 12, 0),
                    'monthlyStartOdo' => array_fill(1, 12, null),
                    'monthlyEndOdo' => array_fill(1, 12, null),
                    'yearStartOdo' => null,
                    'yearEndOdo' => 0,
                    'fleet' => null,
                    'costSummary' => collect(),
                    'monthlyTotals' => array_fill(1, 12, 0),
                    'ytdTotal' => 0,
                    'allLogs' => collect(),
                    'totalServiceCost' => 0,
                    'costPerKm' => 0,
                    'annualKm' => 0,
                    'year' => now()->year,
                    'monthlyCpk' => array_fill(1, 12, null),
                ]);
            }

            $selectedId = $request->query('fleet_id', $fleets->first()->id);
            $fleet = FleetInventory::with('vehicle.driver')->find($selectedId) ?? $fleets->first();

            $year = now()->year;

            // ── Logs for current year — cost summary table ──
            $yearLogs = PreventiveMaintenance::where('fleet_id', $fleet->id)
                ->with('maintenanceTask')
                ->whereYear('last_service_date', $year)
                ->whereNotNull('last_service_date')
                ->orderBy('last_service_date')
                ->get();

            $costSummary = [];
            $monthlyTotals = array_fill(1, 12, 0);
            $ytdTotal = 0;

            foreach ($yearLogs as $log) {
                $taskName = $log->maintenanceTask?->tasks_performed ?? 'Unknown Task';
                $month = $log->last_service_date->month;
                $cost = (float) ($log->last_service_cost ?? 0);

                if (! isset($costSummary[$taskName])) {
                    $costSummary[$taskName] = array_fill(1, 12, 0);
                }

                $costSummary[$taskName][$month] += $cost;
                $monthlyTotals[$month] += $cost;
                $ytdTotal += $cost;
            }

            ksort($costSummary);
            $costSummary = collect($costSummary);

            // ── All logs for the log tab (all time, newest first) ──
            $allLogs = PreventiveMaintenance::where('fleet_id', $fleet->id)
                ->with('maintenanceTask')
                ->orderByDesc('last_service_date')
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'task_name' => $log->maintenanceTask?->tasks_performed ?? 'Unknown Task',
                        'service_date' => $log->last_service_date?->format('M d, Y'),
                        'mileage' => $log->last_service_odo,
                        'cost' => $log->last_service_cost,
                        'remarks' => $log->comments,
                    ];
                });

            // ── Stats ──
            $totalServiceCost = $ytdTotal;

            // ── Monthly kilometer calculation ──
            $allOrderedLogs = PreventiveMaintenance::where('fleet_id', $fleet->id)
                ->whereNotNull('last_service_odo')
                ->whereNotNull('last_service_date')
                ->orderBy('last_service_date')
                ->get();

            $monthlyKm = array_fill(1, 12, 0);
            $monthlyStartOdo = array_fill(1, 12, null);
            $monthlyEndOdo = array_fill(1, 12, null);
            $monthlyCpk = array_fill(1, 12, null);
            $runningOdo = null;

            // Find annual starting baseline
            $firstLogOfYear = $allOrderedLogs->first(fn($l) => $l->last_service_date && $l->last_service_date->year === $year);
            $annualStartingOdo = 0;
            if ($firstLogOfYear) {
                $prevLog = $allOrderedLogs->where('id', '<', $firstLogOfYear->id)->last();
                $annualStartingOdo = $prevLog ? $prevLog->last_service_odo : 0;
            }

            foreach ($allOrderedLogs as $log) {
                $m = $log->last_service_date->month;
                $monthlyStartOdo[$m] ??= $runningOdo;
                $monthlyEndOdo[$m] = $log->last_service_odo;

                $baseline = $monthlyStartOdo[$m] ?? $annualStartingOdo;

                if ($baseline !== null) {
                    $delta = $log->last_service_odo - $baseline;
                    if ($delta > 0) {
                        $monthlyKm[$m] += $delta;
                    }
                }
                $runningOdo = $log->last_service_odo;
            }

            $yearStartOdo = $monthlyStartOdo[1];
            $yearEndOdo = $runningOdo;
            $annualKm = array_sum($monthlyKm);

            // Cost per km per month
            for ($m = 1; $m <= 12; $m++) {
                if ($monthlyKm[$m] > 0) {
                    $monthlyCpk[$m] = round($monthlyTotals[$m] / $monthlyKm[$m], 2);
                }
            }

            $costPerKm = $annualKm > 0 ? round($totalServiceCost / $annualKm, 2) : 0;

            return view('maintenance-manager.dashboard', compact(
                'fleets',
                'drivers',
                'fleet',
                'costSummary',
                'monthlyTotals',
                'ytdTotal',
                'allLogs',
                'totalServiceCost',
                'costPerKm',
                'annualKm',
                'year',
                'monthlyKm',
                'monthlyStartOdo',
                'monthlyEndOdo',
                'yearStartOdo',
                'yearEndOdo',
                'monthlyCpk',
            ));
        }
    }

    public function profile(Request $request)
    {
        $user = Auth::user();
        $userId = Auth::user()->id;
        $role = $user->roles->first()->name;
        $payments = Payment::where('paid_by', $userId)->get();
        $topups = TopupHistory::where('user_id', $userId)->get();
        $wallet = Wallet::where('user_id', $userId)->first();

        if ($role == 'commuter') {
            return view('commuter.profile', [
                'payments' => $payments,
                'topups' => $topups,
                'wallet' => $wallet,
            ]);
        }

        if ($role == 'admin') {
            return view('admin.profile', [
                'user' => $user,
            ]);
        }

        if ($role == 'driver') {
            return view('driver.profile', [
                'user' => $user,
            ]);
        }

        if ($role == 'driver_manager') {
            return view('driver-manager.profile', [
                'user' => $user,
            ]);
        }

        if ($role == 'maintenance_manager') {
            return view('maintenance-manager.profile', [
                'user' => $user,
            ]);
        }
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        activity()->event('Profile Update')->log('User update profile success.');

        return back()->with('success', 'Password successfully updated');
    }

    public function editProfile()
    {
        $user = Auth::user();
        $userId = Auth::user()->id;
        $role = $user->roles->first()->name;

        if ($role == 'commuter') {
            return view('commuter.editprofile');
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request) {}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    public function register(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'confirm-password' => 'required|same:password',
            'terms' => 'required',
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if ($user) {
            Auth::login($user);
            event(new Registered($user));
            $user->assignRole('commuter');

            Wallet::create([
                'user_id' => $user->id,
            ]);

            return redirect()->route('map')->with('success', 'User Successfully Registered!');
        }

        return back()->with('error', 'User Failed to Register.');
    }

    public function login(Request $request)
    {

        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if (Auth::attempt($validated)) {
            $user = Auth::user();
            $userId = Auth::user()->id;
            $role = $user->roles->first()->name;

            $request->session()->regenerate();

            activity()->event('Log In')->log('User login success.');

            return redirect()->route('map')->with('success', 'Logged in Successfully!');
        }

        activity()->event('Log In')->log('User failed to login.');

        return back()->with('error', 'Error logging in');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        activity()->event('Log Out')->log('User logout attempt.');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        activity()->event('Log Out')->log('A user has successfully logged out.');

        return redirect()->route('login')->with('success', 'Successfully Logged out!');
    }

    public function emailVerification()
    {
        $userEmail = Auth::user()->email;
        Mail::to($userEmail)->send(new EmailVerification());

        return view('activate');
    }

    public function forgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function requestPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT ? back()->with(['status' => __($status)]) : back()->withErrors(['email' => __($status)]);
    }

    public function resetPassword(string $token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'confirm-password' => 'required|same:password',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'confirm-password', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                activity()->causedBy($user)->event('Reset Password')->log('User reset password success');

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('status', __($status)) : back()->withErrors(['email' => [__($status)]]);
    }
}
