<?php

use App\Http\Controllers\Admin\CommuterController;
use App\Http\Controllers\Admin\DriverApprovalController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverManagerController;
use App\Http\Controllers\DriverProfileController;
use App\Http\Controllers\FareController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PusherController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleTrackingController;
use App\Models\Fare;
use App\Models\FareRate;
use App\Models\Payment;
use App\Models\TopupHistory;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Changed from /home to / so that the landing page will automatically show up upon starting the server
Route::get('/', function (Request $request) {
    // dd(Auth::user());
    return view('home');
})->name('home');

Route::get('/pusher', [PusherController::class, 'index'])->name('pusher.index');
Route::post('/fire-event', [PusherController::class, 'fireEvent'])->name('fire.event');

Route::post('/track/vehicle/broadcast', [VehicleTrackingController::class, 'broadcastLocation'])->name('vehicle.broadcast');
Route::get('/track/vehicles/active', [VehicleTrackingController::class, 'getActiveVehicles']);

// Route::get('/location', [LocationController::class, 'index'])->name('location.index');
// Route::get('/location/update', [LocationController::class, 'update'])->name('location.update');

// Route::get('/track/{vehicleId}', [VehicleTrackingController::class, 'show']);
// Route::post('/track/{vehicleId}/update', [VehicleTrackingController::class, 'updateLocation']);

Route::get('/register', function () {
    return view('register');
})->name('register');

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::post('/users/logout', [UserController::class, 'logout'])->name('users.logout');
Route::post('/users/register', [UserController::class, 'register'])->name('users.register');
Route::post('/users/login', [UserController::class, 'login'])->name('users.login');

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();

    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6.1'])->name('verification.send');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $latestFare = Fare::get()->last();

    $rates = FareRate::get();

    if ($latestFare) {
        $latestFareId = $latestFare->id;
        $rates = FareRate::where('fare_id', $latestFareId)->get();
    }

    $request->fulfill();

    return view('commuter.dashboard', [
        'rates' => $rates,
    ]);
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::middleware('guest')->group(function () {

    Route::post('/users/register', [UserController::class, 'register'])->name('users.register');
    Route::post('/users/login', [UserController::class, 'login'])->name('users.login');

    Route::get('/driver/register', function () {
        return view('driver-register');
    })->name('driver.register.page');

    Route::post('/driver/register', [DriverController::class, 'store'])->name('driver.register');
    Route::get('/register', function () {
        return view('register');
    })->name('register');

    Route::get('/login', function () {
        return view('login');
    })->name('login');

    Route::get('/forgot-password', function () {
        return view('auth.forgot-password');
    })->name('password.request');

    Route::post('/forgot-password', function (Request $request) {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT ? back()->with(['status' => __($status)]) : back()->withErrors(['email' => __($status)]);
    })->name('password.email');

    Route::get('/reset-password/{token}', function (string $token) {
        return view('auth.reset-password', ['token' => $token]);
    })->name('password.reset');

    Route::post('/reset-password', function (Request $request) {
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

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET ? redirect()->route('login')->with('status', __($status)) : back()->withErrors(['email' => [__($status)]]);
    })->name('password.update');

    Route::get('/map', function () {
        $latestFare = Fare::get()->last();

        $rates = FareRate::get();

        if ($latestFare) {
            $latestFareId = $latestFare->id;
            $rates = FareRate::where('fare_id', $latestFareId)->get();
        }

        return view('commuter.dashboard', [
            'rates' => $rates,
        ]);
    })->name('guest.map');

});

Route::middleware(['auth', 'verified'])->group(function () {

    // Route::get('/dashboard/commuter', function () {
    //     return view('commuter.dashboard');
    // })->name('commuter.dashboard');

    Route::get('/tutorial', function () {
        return view('tutorial');
    })->name('tutorial');

    Route::get('/commuter/profile', function () {
        $userId = Auth::user()->id;
        $payments = Payment::where('paid_by', $userId)->get();
        $topups = TopupHistory::where('user_id', $userId)->get();
        $wallet = Wallet::where('user_id', $userId)->first();

        return view('commuter.profile', [
            'payments' => $payments,
            'topups' => $topups,
            'wallet' => $wallet,
        ]);
    })->name('commuter.profile');

    // Route::get('/dashboard', function () {
    //     return view('commuter.dashboard');
    // })->name('commuter.dashboard');Q
    Route::middleware('role:commuter|admin|driver|driver_manager')->group(function () {
        Route::get('/dashboard', function () {
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

            if ($role == 'admin') {
                return view('commuter.dashboard', [
                    'rates' => $rates,
                ]);
            }

            if ($role == 'driver') {

                if ($user->driver_approval_status !== 'approved') {
                    Auth::logout();

                    return redirect()->route('login')->with('driver_pending', true);
                }

                return view('commuter.dashboard', [
                    'rates' => $rates,
                    'recentReceipts' => $recentReceipts,
                ]);
                // return view('driverdashboard');
            }

            if ($role == 'commuter') {
                return view('commuter.dashboard', [
                    'rates' => $rates,
                    'recentReceipts' => $recentReceipts,
                    'balance' => $wallet->balance ?? 0.00,
                ]);
            }

            if ($role == 'driver_manager') {
                return view('driver-manager.dashboard');
            }

        })->name('commuter.dashboard');
    });

    Route::get('/payment', function () {
        return view('commuter.payment');
    })->name('payment');

    Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');
    Route::post('/payment/process', [PaymentController::class, 'process'])->name('payment.process');
    Route::get('/payment/history', [PaymentController::class, 'history'])->name('payment.history');
    Route::get('/payment/receipt/{id}', [PaymentController::class, 'showReceipt'])->name('payment.showReceipt');
    Route::get('/payment/topup', [PaymentController::class, 'topup'])->name('payment.topup');
    Route::post('/payment/topup/process', [PaymentController::class, 'topupProcess'])->name('payment.topup.process');
    Route::get('/payment/topup/history', [PaymentController::class, 'topupHistory'])->name('payment.topup.history');

    Route::get('/admin/topups', [PaymentController::class, 'showTopupsAdmin'])->name('admin.topups');
    Route::get('/transactions', [PaymentController::class, 'showTransactions'])->name('faretransactions');
    Route::get('/trasanctions/receipt/{id}', [PaymentController::class, 'showReceiptAdmin'])->name('admin.receipt.show');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/commuters/create', [CommuterController::class, 'create'])->name('admin.commuters.create');
        Route::post('/admin/commuters', [CommuterController::class, 'store'])->name('admin.commuters.store');
        Route::get('/admin/commuters/{user}/edit', [CommuterController::class, 'edit'])->name('admin.commuters.edit');
        Route::put('/admin/commuters/{user}', [CommuterController::class, 'update'])->name('admin.commuters.update');
        Route::delete('/admin/commuters/{user}', [CommuterController::class, 'destroy'])->name('admin.commuters.destroy');
        Route::get('/admin/commuters', [CommuterController::class, 'index'])->name('admin.commuters.index');

        Route::get('/admin/drivers/create', [DriverApprovalController::class, 'create'])->name('admin.drivers.create');
        Route::post('/admin/drivers', [DriverApprovalController::class, 'store'])->name('admin.drivers.store');
        Route::get('/admin/drivers/{user}/edit', [DriverApprovalController::class, 'edit'])->name('admin.drivers.edit');
        Route::put('/admin/drivers/{user}', [DriverApprovalController::class, 'update'])->name('admin.drivers.update');
        Route::delete('/admin/drivers/{user}', [DriverApprovalController::class, 'destroy'])->name('admin.drivers.destroy');
        Route::get('/admin/drivers/{user}/license', [DriverApprovalController::class, 'showLicense'])->name('admin.drivers.license');
        Route::post('/admin/drivers/{user}/approve', [DriverApprovalController::class, 'approve'])->name('admin.drivers.approve');
        // Route::post('/admin/drivers/{user}/unapprove', [DriverApprovalController::class, 'unapprove'])->name('admin.drivers.unapprove');
        Route::post('/admin/drivers/{user}/reject', [DriverApprovalController::class, 'reject'])->name('admin.drivers.reject');
        Route::get('/admin/drivers', [DriverApprovalController::class, 'index'])->name('admin.drivers.index');
    });

    Route::middleware('role:driver')->group(function () {
        Route::get('/driverprofile', [DriverProfileController::class, 'show'])->name('driverprofile');
        Route::put('/driverprofile', [DriverProfileController::class, 'update'])->name('driverprofile.update');
    });

    Route::middleware('role:driver_manager')->group(function () {
        Route::get('/time-keeping', [DriverManagerController::class, 'timeKeeping'])->name('driver-manager.time-keeping');
        Route::get('/violations-log', [DriverManagerController::class, 'violationsLog'])->name('driver-manager.violations-log');
        Route::get('/violation-codes', [DriverManagerController::class, 'violationCodes'])->name('driver-manager.violation-codes');
    });

    Route::get('/adminprofile', function () {
        $info = Auth::user();

        return view('admin.adminprofile', [
            'info' => $info,
        ]);
    })->name('adminprofile');

    Route::get('/admindashboard', function () {

        return view('admin.dashboard', [
            'totalRevenue' => Payment::sum('price'),
            'totalFundsAdded' => TopupHistory::sum('amount_added'),
            'activeUsersCount' => Payment::distinct('paid_by')->count(), // Or your preferred logic
            'recentFares' => Payment::with('user')->latest()->take(5)->get(),
            'recentTopups' => TopupHistory::with('user')->latest()->take(5)->get(),
        ]);
    })->name('admin.dashboard');

    Route::get('/driverdashboard', [DriverController::class, 'index'])->name('driver.dashboard');

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

    Route::get('/profile/edit', function () {
        return view('commuter.editprofile');
    })->name('profile.edit');

    Route::patch('/profile/update', function (Request $request) {

        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
        ]);

        $userId = Auth::user()->id;
        $user = User::where('id', $userId)->first();

        if ($user->update([
            'password' => $request->password_confirmation,
        ])) {
            return redirect()->route('commuter.profile')->with('success', 'password successfully updated');
        }

        return back()->with('error', 'Failed to update password');
    })->name('profile.update');

    Route::get('/fare/{id}', [FareController::class, 'view'])->middleware('role:admin')->name('fares.view');
    Route::get('/fares', [FareController::class, 'index'])->middleware('role:admin')->name('fares.index');
    Route::put('/fare/upload', [FareController::class, 'upload'])->middleware('role:admin')->name('fares.upload');
    Route::put('/fare/update', [FareController::class, 'bulkUpdate'])->middleware('role:admin')->name('fares.bulk-update');
    Route::delete('/fare/{id}/delete', [FareController::class, 'delete'])->middleware('role:admin')->name('fares.destroy');

    // Route::get('/commuter/commuter', function () {
    //     return view('commuter.commuter');
    // })->name('commuter.commuter');

    Route::resource('users', UserController::class);
    Route::resource('routes', RouteController::class)->middleware('role:admin');
    Route::resource('rates', RateController::class)->middleware('role:admin');

});
