    <?php

use App\Http\Controllers\FareController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PusherController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleTrackingController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\Admin\CommuterController;
use App\Http\Controllers\Admin\DriverApprovalController;
use App\Http\Controllers\DriverProfileController;
use App\Models\User;
use App\Models\Fare;
use App\Models\FareRate;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

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

Route::post('/track/vehicle/broadcast', [VehicleTrackingController::class, 'broadcastLocation']);
Route::get('/track/vehicles/active', [VehicleTrackingController::class, 'getActiveVehicles']);

// Route::get('/location', [LocationController::class, 'index'])->name('location.index');
// Route::get('/location/update', [LocationController::class, 'update'])->name('location.update');

// Route::get('/track/{vehicleId}', [VehicleTrackingController::class, 'show']);
// Route::post('/track/{vehicleId}/update', [VehicleTrackingController::class, 'updateLocation']);

Route::get('/register', function () {
    return view('register');
})->name('register');

Route::get('/login',function (){
    return view('login');
})->name('login');



Route::post('/users/logout', [UserController::class, 'logout'])->name('users.logout');
Route::post('/users/register', [UserController::class,'register'])->name('users.register');
Route::post('/users/login', [UserController::class, 'login'])->name('users.login');

Route::get('/email/verify', function() {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::post('/email/verification-notification', function(Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6.1'])->name('verification.send');

Route::get('/email/verify/{id}/{hash}', function(EmailVerificationRequest $request) {
    $latestFare = Fare::get()->last();

    $rates = FareRate::get();

    if($latestFare) {
        $latestFareId = $latestFare->id;
        $rates = FareRate::where('fare_id', $latestFareId)->get();
    }

    $request->fulfill();
    return view('commuter.dashboard', [
        'rates' => $rates
    ]);
})->middleware(['auth','signed'])->name('verification.verify');


Route::middleware('guest')->group(function() {

    Route::post('/users/register', [UserController::class,'register'])->name('users.register');
    Route::post('/users/login', [UserController::class, 'login'])->name('users.login');

   
     Route::get('/driver/register', function () {
        return view('driver-register');
    })->name('driver.register.page');

    Route::post('/driver/register', [DriverController::class, 'store'])->name('driver.register');
    Route::get('/register', function () {
        return view('register');
    })->name('register');

    Route::get('/login',function (){
        return view('login');
    })->name('login');

    Route::get('/forgot-password', function() {
        return view('auth.forgot-password');
    })->name('password.request');

    Route::post('/forgot-password', function(Request $request) {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT ? back()->with(['status' => __($status)]) : back()->withErrors(['email' => __($status)]);
    })->name('password.email');

    Route::get('/reset-password/{token}', function(string $token) {
        return view('auth.reset-password', ['token' => $token]);
    })->name('password.reset');

    Route::post('/reset-password', function(Request $request) {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'confirm-password' => 'required|same:password'
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'confirm-password', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
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

        if($latestFare) {
            $latestFareId = $latestFare->id;
            $rates = FareRate::where('fare_id', $latestFareId)->get();
        }

        return view('commuter.dashboard', [
            'rates' => $rates
        ]);
    })->name('guest.map');


    Route::get('/tutorial', function () {
        return view('tutorial');
    })->name('tutorial');

});

Route::middleware(['auth', 'verified'])->group(function (){

    // Route::get('/dashboard/commuter', function () {
    //     return view('commuter.dashboard');
    // })->name('commuter.dashboard');

    Route::get('/commuter/profile', function () {
        return view('commuter.profile');
    })->name('commuter.profile');

    // Route::get('/dashboard', function () {
    //     return view('commuter.dashboard');
    // })->name('commuter.dashboard');Q
    Route::middleware('role:commuter|admin|driver')->group(function() {
        Route::get('/dashboard', function () {
            $user = Auth::user();
            $role = $user->roles->first()->name;
            $latestFare = Fare::get()->last();

            $rates = FareRate::get();

            if($latestFare) {
                $latestFareId = $latestFare->id;
                $rates = FareRate::where('fare_id', $latestFareId)->get();
            }

            if($role == 'admin') {
                return view('admin.dashboard');
            }

            if($role == 'driver') {
                if ($user->driver_approval_status !== 'approved') {
                    Auth::logout();
                    return redirect()->route('login')->with('driver_pending', true);
                }
                return view('driverdashboard');
            }

            if($role == 'commuter') {
                return view('commuter.dashboard', [
                    'rates' => $rates
                ]);
            }

        })->name('commuter.dashboard');
    });

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
        Route::post('/admin/drivers/{user}/unapprove', [DriverApprovalController::class, 'unapprove'])->name('admin.drivers.unapprove');
        Route::post('/admin/drivers/{user}/reject', [DriverApprovalController::class, 'reject'])->name('admin.drivers.reject');
        Route::get('/admin/drivers', [DriverApprovalController::class, 'index'])->name('admin.drivers.index');
    });

    Route::middleware('role:driver')->group(function () {
        Route::get('/driverprofile', [DriverProfileController::class, 'show'])->name('driverprofile');
        Route::put('/driverprofile', [DriverProfileController::class, 'update'])->name('driverprofile.update');
    });

    Route::get('/adminprofile', function () {
        return view('adminprofile');
    })->name('adminprofile');

    Route::get('/admindashboard',function (){
        return view('admindashboard');
    })->name('admindashboard');
     
  
    // Route::get('/driverdashboard',function (){

    //     return view('driverdashboard');
    // })->name('driverdashboard');

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');

    Route::get('/fare/{id}', [FareController::class, 'view'])->middleware('role:admin')->name('fares.view');
    Route::get('/fares', [FareController::class, 'index'])->middleware('role:admin')->name('fares.index');
    Route::put('/fare/upload', [FareController::class, 'upload'])->middleware('role:admin')->name('fares.upload');
    Route::delete('/fare/{id}/delete', [FareController::class, 'delete'])->middleware('role:admin')->name('fares.destroy');

    // Route::get('/commuter/commuter', function () {
    //     return view('commuter.commuter');
    // })->name('commuter.commuter');

    Route::resource('users', UserController::class);
    Route::resource('routes', RouteController::class)->middleware('role:admin');
    Route::resource('rates', RateController::class)->middleware('role:admin');

});




