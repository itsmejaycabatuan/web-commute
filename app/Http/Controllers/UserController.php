<?php

namespace App\Http\Controllers;

use App\Helpers\LocationPrivacy;
use App\Mail\EmailVerification;
use App\Models\DevMarker;
use App\Models\Driver;
use App\Models\Fare;
use App\Models\FareRate;
use App\Models\Payment;
use App\Models\PreventiveMaintenance;
use App\Models\TimeKeeping;
use App\Models\TopupHistory;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLocationHistory;
use App\Models\ViolationLog;
use App\Models\Wallet;
use App\Services\FleetMaintenanceService;
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
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function map(Request $request)
    {

        activity()->event('Map')->log('Action performed: map');

        $user = Auth::user();
        $rates = FareRate::get();

        $userId = Auth::user()->id;
        $role = $user->roles->first()->name;
        $latestFare = Fare::get()->last();
        $wallet = Wallet::where('user_id', $userId)->first();
        $driverStatus = $request->user()->driver?->status ?? 'inactive';

        $recentReceipts = Payment::where('paid_by', $userId)->latest()->take(3)->get();

        if ($latestFare) {
            $latestFareId = $latestFare->id;
            $rates = FareRate::where('fare_id', $latestFareId)->get();
        }

        if ($role == 'admin') {
            $dummyMarkers = app()->environment('local')
                           ? DevMarker::where('user_id', Auth::id())->latest()->get()
                           : collect();

            return view('map', [
                'rates' => $rates,
                'dummyMarkers' => $dummyMarkers,

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

            $todayRecord = TimeKeeping::where('driver_id', $driver->id)
                ->whereDate('date', today())
                ->first();

            if ($driver->is_approved != true && $driver->is_rejected != true) {
                Auth::logout();

                return redirect()->route('login')->with('driver_pending', true);
            }

            if ($driver->is_rejected == true) {
                Auth::logout();

                return redirect()->route('login')->with('driver_rejected', true);
            }

            return view('map', [
                'rates' => $rates,
                'recentReceipts' => $recentReceipts,
                'todayRecord' => $todayRecord,
                'driverStatus' => $driverStatus,
                'driverVehicleId' => $driver->vehicle->first()?->id,
            ]);
        }

        if ($role == 'commuter') {
            $obfuscatedMarkers = collect();
            $dummyMarkers = app()->environment('local')
            ? DevMarker::where('user_id', Auth::id())->latest()->get()
            : collect();
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
                'dummyMarkers' => $dummyMarkers,
                'rates' => $rates,
                'recentReceipts' => $recentReceipts,
                'balance' => $wallet->balance ?? 0.00,
                'obfuscatedMarkers' => $obfuscatedMarkers,
            ]);
        }
    }

    public function dashboard(Request $request, FleetMaintenanceService $fleet)
    {
        activity()->event('Dashboard')->log('Action performed: dashboard');
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
            $revenueByDay = Payment::where('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as date, SUM(price) as total')
                ->groupBy('date')->orderBy('date')->pluck('total', 'date')->toArray();

            $topupsByDay = TopupHistory::where('created_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(created_at) as date, SUM(amount_added) as total')
                ->groupBy('date')->orderBy('date')->pluck('total', 'date')->toArray();

            return view('admin.dashboard', [
                'totalRevenue' => $totalRevenue,
                'totalFundsAdded' => $totalFundsAdded,
                'activeUsersCount' => $activeUsersCount,
                'recentFares' => $recentFares,
                'recentTopups' => $recentTopups,
                'revenueByDay' => $revenueByDay,
                'topupsByDay' => $topupsByDay,
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

            // ── Violations data ──
            $violationLogs = ViolationLog::with('violationCode')
                ->where('user_id', $userId)
                ->latest()
                ->take(5)
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
                        'date' => Carbon::parse($v->date_of_violation)->format('M d, Y'),
                        'time' => Carbon::parse($v->time_of_violation)->format('g:i A'),
                        'fine' => (float) $v->violation_fine,
                        'penalty' => $v->additional_penalties,
                    ];
                });

            $totalViolations = ViolationLog::where('user_id', $userId)->count();
            $totalViolationFines = ViolationLog::where('user_id', $userId)->sum('violation_fine');

            return view('driver.dashboard', [
                'driver' => $driver,
                'todayRecord' => $todayRecord,
                'recentTimeKeeping' => $recentTimeKeeping,
                'weekHours' => $weekHours,
                'weekOvertime' => $weekOvertime,
                'vehicle' => $vehicle,
                'total_distance' => $total_distance ?? 0,
                'violationLogs' => $violationLogs,
                'totalViolations' => $totalViolations,
                'totalViolationFines' => $totalViolationFines,
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
            // The dashboard shows the same fleet maintenance summary as the full
            // page (/fleet-maintenance-log) so the two can never disagree.
            $vehicles = Vehicle::with('driver')
                ->orderBy('plate_number')
                ->get();

            $drivers = Driver::orderBy('name')->get();

            if ($vehicles->isEmpty()) {
                return view('maintenance-manager.dashboard', array_merge(
                    ['vehicles' => collect(), 'drivers' => $drivers, 'vehicle' => null],
                    $fleet->emptySummary(),
                ));
            }

            $selectedId = $request->query('vehicle_id', $vehicles->first()->id);
            $vehicle = Vehicle::with('driver')->find($selectedId) ?? $vehicles->first();

            return view('maintenance-manager.dashboard', array_merge(
                [
                    'vehicles' => $vehicles,
                    'drivers' => $drivers,
                    'vehicle' => $vehicle,
                ],
                $fleet->summaryFor($vehicle),
            ));
        }
    }

    public function profile(Request $request)
    {
        activity()->event('Profile')->log('Action performed: profile');
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
    }

    public function updateProfile(Request $request)
    {
        activity()->event('Updateprofile')->log('Action performed: updateProfile');
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
        //
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
    public function destroy(Request $request)
    {
        activity()->event('Destroy')->log('Action performed: destroy');
        $userId = Auth::user()->id;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        User::destroy($userId);

        return redirect()->route('login')->with('success', 'Account deleted successfully.');
    }

    public function register(Request $request)
    {
        activity()->event('Register')->log('Action performed: register');

        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'confirm-password' => 'required|same:password',
            'terms' => 'accepted',
        ]);

        try {
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
        } catch (\Exception $e) {
            activity()->event('Register')->log('Database error during registration.');

            throw ValidationException::withMessages([
                'email' => 'A system error occurred while creating your account. Please try again later.',
            ]);
        }

        $user->assignRole('commuter');

        Wallet::create([
            'user_id' => $user->id,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('verification.notice')->with('success', 'Account created, please verify your email!');
    }

    public function login(Request $request)
    {
        activity()->event('Login')->log('Action performed: login');

        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $attempted = Auth::attempt($validated, $request->has('remember'));
        } catch (\Exception $e) {
            // E1 - Database / auth provider failure: cancel the login gracefully
            activity()->event('Login')->log('System error during login.');

            throw ValidationException::withMessages([
                'credentials' => 'Login is unavailable right now. Please try again later.',
            ]);
        }

        if (! $attempted) {
            activity()->event('Log In')->log('User failed to login.');

            // E3/E4 - generic message on purpose: never reveal whether the email exists
            throw ValidationException::withMessages([
                'credentials' => 'The provided credentials do not match our records.',
            ]);
        }

        // Regenerate the session id before anything else (session fixation guard),
        // including the unverified-email path below.
        $request->session()->regenerate();

        $user = Auth::user();

        // UCN_SC_E018 A4 / UCN_SC_E012 — a suspended account is refused here, before
        // the session becomes usable, so a suspended commuter or driver is never
        // signed in.
        if ($user->isSuspended()) {
            activity()->event('Login')->log('Suspended user attempted to log in.');

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'credentials' => 'This account has been suspended. Please contact the administrator.',
            ]);
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        activity()->event('Log In')->log('User login success.');

        return redirect()->route('map')->with('success', 'Logged in Successfully!');
    }

    public function logout(Request $request)
    {
        activity()->event('Logout')->log('Action performed: logout');

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
        activity()->event('Emailverification')->log('Action performed: emailVerification');
        $userEmail = Auth::user()->email;
        Mail::to($userEmail)->send(new EmailVerification());

        return view('activate');
    }

    public function forgotPassword()
    {
        activity()->event('Forgotpassword')->log('Action performed: forgotPassword');

        return view('auth.forgot-password');
    }

    public function requestPassword(Request $request)
    {
        activity()->event('Requestpassword')->log('Action performed: requestPassword');
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT ? back()->with(['status' => __($status)]) : back()->withErrors(['email' => __($status)]);
    }

    public function resetPassword(string $token, Request $request)
    {
        activity()->event('Resetpassword')->log('Action performed: resetPassword');
        $email = $request->query('email');

        return view('auth.reset-password', ['token' => $token, 'email' => $email]);
    }

    public function updatePassword(Request $request)
    {
        activity()->event('Updatepassword')->log('Action performed: updatePassword');
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

        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('success', __($status)) : back()->withErrors(['email' => [__($status)]]);
    }
}
