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
use App\Support\Audit;
use App\Services\FleetMaintenanceService;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function map(Request $request)
    {


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
                // Only an active vehicle can be driven (and broadcast) live.
                'driverVehicleId' => $driver->vehicle()->where('status', 'active')->first()?->id,
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

    /**
     * UCN_SC_E025 E2/E3/E4 — a role without a dashboard, or a failing query, is
     * answered with a message instead of an empty page or a raw 500.
     */
    public function dashboard(Request $request, FleetMaintenanceService $fleet)
    {
        try {
            $response = $this->renderDashboard($request, $fleet);
        } catch (QueryException $e) {
            report($e);
            \Illuminate\Support\Facades\Log::error('Database error while building the dashboard.', ['area' => 'Dashboard', 'exception' => $e->getMessage()]);

            return redirect()->route('map')->with('error', 'The dashboard could not be loaded. Please try again later.');
        }

        return $response ?? redirect()->route('map')->with('error', 'Dashboard unavailable.');
    }

    private function renderDashboard(Request $request, FleetMaintenanceService $fleet)
    {
        $user = Auth::user();
        $userId = Auth::user()->id;
        $role = $user->roles->first()?->name;
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
            $totalRevenue = Payment::settled()->sum('price');
            $totalFundsAdded = TopupHistory::completed()->sum('amount_added');
            $activeUsersCount = Payment::settled()->distinct()->count('paid_by');
            $recentFares = Payment::settled()->with('user')->latest()->take(5)->get();
            $recentTopups = TopupHistory::with('user')->latest()->take(5)->get();
            $revenueByDay = Payment::settled()->where('paid_at', '>=', now()->subDays(7))
                ->selectRaw('DATE(paid_at) as date, SUM(price) as total')
                ->groupBy('date')->orderBy('date')->pluck('total', 'date')->toArray();

            $topupsByDay = TopupHistory::completed()->where('created_at', '>=', now()->subDays(7))
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
        $user = Auth::user();
        $userId = Auth::user()->id;
        $role = $user->roles->first()?->name;

        // UCN_SC_E025 E1 — only commuters have a profile page.
        if ($role !== 'commuter') {
            return redirect()->route('map')->with('error', 'Profile page unavailable.');
        }

        // UCN_SC_E025 E3 — a failing query is logged and reported, not a raw 500.
        try {
            $payments = Payment::where('paid_by', $userId)->get();
            $topups = TopupHistory::where('user_id', $userId)->get();
            $wallet = Wallet::where('user_id', $userId)->first();
        } catch (QueryException $e) {
            report($e);
            \Illuminate\Support\Facades\Log::error('Database error while building the profile.', ['area' => 'Profile', 'exception' => $e->getMessage()]);

            return redirect()->route('map')->with('error', 'The profile could not be loaded. Please try again later.');
        }

        return view('commuter.profile', [
            'payments' => $payments,
            'topups' => $topups,
            'wallet' => $wallet,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'The current password is incorrect.',
            'password.different' => 'The new password must be different from your current password.',
        ]);

        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // A changed password signs the account out everywhere else.
        $user->forgetOtherSessions($request->session()->getId());

        Audit::log(Audit::AUTH, 'password_changed', 'Changed own password', $user, ['other_sessions_ended' => true], $user);

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
        $user = Auth::user();
        $userId = $user->id;

        // A driver who still holds a vehicle cannot remove their account: the
        // vehicle must be released by the Maintenance Manager first.
        if (Driver::where('user_id', $userId)->whereHas('vehicle')->exists()) {
            return back()->with('error', 'You are still assigned to a vehicle. Ask the maintenance manager to unassign it before deleting your account.');
        }

        // Money in the wallet cannot be refunded (there is no refund flow), so the account may not be
        // removed while it holds any — the same rule the Admin is held to for a commuter (UCN_SC_E018 E8).
        $balance = (float) ($user->wallet?->balance ?? 0);
        if ($balance > 0) {
            return back()->with('error', 'Your wallet still holds PHP '.number_format($balance, 2).'. Use it up before deleting your account.');
        }

        // With payments or top-ups behind it the account is anonymised, not deleted, so the ledger survives.
        $outcome = $user->hasFinancialHistory() ? 'anonymised' : 'deleted';

        // Logged BEFORE the sign-out so the entry still knows who did it; the email is kept in the
        // properties because the account is about to lose it.
        Audit::log(Audit::ACCOUNT, 'deleted_self', 'Deleted own account '.$user->email, $user, [
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
            'outcome' => $outcome,
        ], $user);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->eraseAccount(); // already audited above, with the actor

        return redirect()->route('login')->with('success', 'Account deleted successfully.');
    }

    public function register(Request $request)
    {

        $request->validate([
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'confirm-password' => 'required|same:password',
            'terms' => 'accepted',
        ], [
            'password.min' => 'The password field must be at least 8 characters.',
            'confirm-password.same' => 'The password confirmation does not match.',
        ]);

        // UCN_SC_E002 E6 — the account, its role and its wallet are created
        // together or not at all, so a failure leaves no half-registered user.
        try {
            $user = DB::transaction(function () use ($request) {
                $user = User::create([
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);

                $user->assignRole('commuter');

                Wallet::create([
                    'user_id' => $user->id,
                ]);

                return $user;
            });
        } catch (\Exception $e) {
            report($e);
            \Illuminate\Support\Facades\Log::error('Database error during registration.', ['area' => 'Register', 'exception' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'email' => 'A system error occurred while creating your account. Please try again later.',
            ]);
        }

        event(new Registered($user));

        Audit::log(Audit::ACCOUNT, 'registered', 'Registered commuter account '.$user->email, $user, [
            'role' => 'commuter',
            'source' => 'self-registration',
        ], $user);

        Auth::login($user);

        return redirect()->route('verification.notice')->with('success', 'Account created, please verify your email!');
    }

    public function login(Request $request)
    {

        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $attempted = Auth::attempt($validated, $request->has('remember'));
        } catch (\Exception $e) {
            // E1 - Database / auth provider failure: cancel the login gracefully
            \Illuminate\Support\Facades\Log::error('System error during login.', ['area' => 'Login', 'exception' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'credentials' => 'Login is unavailable right now. Please try again later.',
            ]);
        }

        if (! $attempted) {
            // The email is recorded so repeated attempts on one account are visible; the password never is.
            Audit::log(Audit::AUTH, 'login_failed', 'Failed sign-in for '.$validated['email'], null, ['email' => $validated['email']]);

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
            Audit::log(Audit::AUTH, 'login_blocked', 'Suspended account tried to sign in: '.$user->email, $user, ['reason' => 'suspended'], $user);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'credentials' => 'This account has been suspended. Please contact the administrator.',
            ]);
        }

        Audit::log(Audit::AUTH, 'login', 'Signed in '.$user->email, $user, [
            'remember' => $request->has('remember'),
            'email_verified' => $user->hasVerifiedEmail(),
        ], $user);

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        $response = redirect()->route('map')->with('success', 'Logged in Successfully!');

        // A driver is reminded at every sign-in while their licence is expiring or expired.
        if ($notice = $user->driver?->licenseNotice()) {
            $response->with('warning', $notice);
        }

        return $response;
    }

    public function logout(Request $request)
    {

        $user = Auth::user();

        if ($user) {
            Audit::log(Audit::AUTH, 'logout', 'Signed out '.$user->email, $user, [], $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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

    public function resetPassword(string $token, Request $request)
    {
        $email = $request->query('email');

        return view('auth.reset-password', ['token' => $token, 'email' => $email]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'confirm-password' => 'required|same:password',
        ], [
            'confirm-password.same' => 'The password confirmation does not match.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'confirm-password', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                Audit::log(Audit::AUTH, 'password_reset', 'Reset password through the emailed link', $user, [], $user);

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('success', __($status)) : back()->withErrors(['email' => [__($status)]]);
    }
}
