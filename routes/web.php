<?php

use App\Http\Controllers\Admin\CommuterController;
use App\Http\Controllers\Admin\DriverApprovalController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DevMarkerController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverManagerController;
use App\Http\Controllers\FareController;
use App\Http\Controllers\MaintenanceManagerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PusherController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleTrackingController;
use App\Models\Fare;
use App\Models\FareRate;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/logout', [UserController::class, 'logout'])->name('users.logout');
Route::post('/register', [UserController::class, 'register'])->name('users.register');
Route::post('/login', [UserController::class, 'login'])->name('users.login');

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

    return view('map', [
        'rates' => $rates,
    ]);
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::middleware('guest')->group(function () {
    Route::get('/map/guest', function () {
        $latestFare = Fare::get()->last();

        $rates = FareRate::get();

        if ($latestFare) {
            $latestFareId = $latestFare->id;
            $rates = FareRate::where('fare_id', $latestFareId)->get();
        }

        return view('map', [
            'rates' => $rates,
        ]);
    })->name('guest.map');

    Route::get('/register/driver', [DriverController::class, 'create'])->name('driver.register.page');
    Route::post('/register/driver', [DriverController::class, 'store'])->name('driver.register');

    Route::get('/forgot-password', [UserController::class, 'forgotPassword'])->name('password.request');
    Route::post('/forgot-password', [UserController::class, 'requestPassword'])->name('password.email');
    Route::get('/reset-password/{token}', [UserController::class, 'resetPassword'])->name('password.reset');
    Route::post('/reset-password', [UserController::class, 'updatePassword'])->name('password.update');
});

Route::middleware(['role:commuter|admin|driver|driver_manager|maintenance_manager', 'auth', 'verified'])->group(function () {
    Route::get('/map', [UserController::class, 'map'])->name('map');
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::patch('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

    Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');
    Route::post('/payment/process', [PaymentController::class, 'process'])->name('payment.process');
    Route::get('/payment/history', [PaymentController::class, 'history'])->name('payment.history');
    Route::get('/payment/receipt/{id}', [PaymentController::class, 'showReceipt'])->name('payment.showReceipt');
    Route::get('/payment/topup', [PaymentController::class, 'topup'])->name('payment.topup');
    Route::post('/payment/topup/process', [PaymentController::class, 'topupProcess'])->name('payment.topup.process');
    Route::get('/payment/topup/history', [PaymentController::class, 'topupHistory'])->name('payment.topup.history');

    Route::get('/topups', [PaymentController::class, 'showTopupsAdmin'])->name('admin.topups');
    Route::get('/transactions', [PaymentController::class, 'showTransactions'])->name('faretransactions');
    Route::get('/trasanctions/receipt/{id}', [PaymentController::class, 'showReceiptAdmin'])->name('admin.receipt.show');

    Route::middleware('role:admin')->group(function () {
        //        Route::get('/dashboard/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');

        Route::get('/admin/commuters/create', [CommuterController::class, 'create'])->name('admin.commuters.create');
        Route::post('/admin/commuters', [CommuterController::class, 'store'])->name('admin.commuters.store');
        Route::get('/admin/commuters/{user}/edit', [CommuterController::class, 'edit'])->name('admin.commuters.edit');
        Route::put('/admin/commuters/{user}', [CommuterController::class, 'update'])->name('admin.commuters.update');
        Route::delete('/admin/commuters/{user}', [CommuterController::class, 'destroy'])->name('admin.commuters.destroy');
        Route::get('/admin/commuters', [CommuterController::class, 'index'])->name('admin.commuters.index');

        Route::get('/admin/drivers', [DriverApprovalController::class, 'index'])->name('admin.drivers.index');
        Route::post('/admin/drivers', [DriverApprovalController::class, 'store'])->name('admin.drivers.store');
        Route::get('/admin/drivers/create', [DriverApprovalController::class, 'create'])->name('admin.drivers.create');
        Route::get('/admin/drivers/{user}/edit', [DriverApprovalController::class, 'edit'])->name('admin.drivers.edit');
        Route::put('/admin/drivers/{user}', [DriverApprovalController::class, 'update'])->name('admin.drivers.update');
        Route::delete('/admin/drivers/{driver}', [DriverApprovalController::class, 'destroy'])->name('admin.drivers.destroy');
        Route::get('/admin/drivers/{user}/license', [DriverApprovalController::class, 'showLicense'])->name('admin.drivers.license');
        Route::post('/admin/drivers/{user}/approve', [DriverApprovalController::class, 'approve'])->name('admin.drivers.approve');
        Route::put('/admin/drivers/{user}/reject', [DriverApprovalController::class, 'reject'])->name('admin.drivers.reject');
    });

    Route::middleware('role:driver_manager')->group(function () {
        Route::get('/time-keeping', [DriverManagerController::class, 'timeKeeping'])->name('driver-manager.time-keeping');
        Route::post('/time-keeping', [DriverManagerController::class, 'timeKeepingStore'])->name('driver-manager.time-keeping.store');
        Route::get('/violations-log', [DriverManagerController::class, 'violationsLog'])->name('driver-manager.violations-log');
        Route::post('/violations-log', [DriverManagerController::class, 'storeViolationLog'])->name('driver-manager.violations-log.store');
        Route::post('/violations-log/bulk', [DriverManagerController::class, 'storeViolationLogBulk'])->name('driver-manager.violations-log.store-bulk');
        Route::get('/violation-codes', [DriverManagerController::class, 'violationCodes'])->name('driver-manager.violation-codes');
        Route::put('/violation-codes/{id}/update', [DriverManagerController::class, 'updateViolationCode'])->name('violation-codes.update');
        Route::post('/violation-codes/store', [DriverManagerController::class, 'storeViolationCode'])->name('violation-codes.store');
        Route::delete('/violation-codes/{id}/delete', [DriverManagerController::class, 'destroyViolationCode'])->name('violation-codes.destroy');
    });

    Route::middleware('role:maintenance_manager')->group(function () {
        Route::get('/preventive-maintenance', [MaintenanceManagerController::class, 'preventiveMaintenance'])->name('maintenance-manager.preventive-maintenance');
        Route::post('/preventive-maintenance', [MaintenanceManagerController::class, 'preventiveMaintenanceStore'])->name('maintenance-manager.preventive-maintenance.store');
        Route::get('/maintenance-logs', [MaintenanceManagerController::class, 'maintenanceLogs'])->name('maintenance-manager.maintenance-calendar');
        Route::get('/maintenance-tasks', [MaintenanceManagerController::class, 'maintenanceTasks'])->name('maintenance-manager.maintenance-tasks');
        Route::post('/maintenance-tasks', [MaintenanceManagerController::class, 'maintenanceTasksStore'])->name('maintenance-manager.maintenance-tasks.store');
        Route::put('/maintenance-tasks/{task}', [MaintenanceManagerController::class, 'maintenanceTasksUpdate'])->name('maintenance-manager.maintenance-tasks.update');
        Route::delete('/maintenance-tasks/{task}', [MaintenanceManagerController::class, 'maintenanceTasksDestroy'])->name('maintenance-manager.maintenance-tasks.destroy');
        Route::get('/vehicle-maintenance-log', [MaintenanceManagerController::class, 'vehicleLog'])->name('maintenance-manager.vehicle-maintenance-log');
        Route::post('/vehicle-maintenance-log', [MaintenanceManagerController::class, 'vehicleLogStore'])->name('maintenance-manager.vehicle-maintenance-log.store');
        Route::patch('/vehicle-maintenance-log/{log}', [MaintenanceManagerController::class, 'vehicleLogUpdate'])->name('maintenance-manager.vehicle-maintenance-log.update');
        Route::delete('/vehicle-maintenance-log/{log}', [MaintenanceManagerController::class, 'vehicleLogDelete'])->name('maintenance-manager.vehicle-maintenance-log.destroy');
        Route::get('/fleet-maintenance-log', [MaintenanceManagerController::class, 'fleetLog'])->name('maintenance-manager.fleet-maintenance-log');
        Route::get('/fleet-inventory', [MaintenanceManagerController::class, 'fleetInventory'])->name('maintenance-manager.fleet-inventory');
        Route::post('/fleet-inventory', [MaintenanceManagerController::class, 'fleetInventoryStore'])->name('maintenance-manager.fleet-inventory.store');
        Route::delete('/fleet-inventory/{id}/delete', [MaintenanceManagerController::class, 'fleetInventoryDelete'])->name('maintenance-manager.fleet-inventory.destroy');
        Route::patch('/fleet-inventory/{id}/update', [MaintenanceManagerController::class, 'fleetInventoryUpdate'])->name('maintenance-manager.fleet-inventory.update');
    });

    Route::middleware('role:driver')->group(function () {
        Route::get('/timekeeping', [DriverController::class, 'timekeeping'])->name('driver.timekeeping');
        Route::get('/violations', [DriverController::class, 'violations'])->name('driver.violations');
        Route::post('/timekeeping/clock-in', [DriverController::class, 'clockIn'])->name('driver.timekeeping.clock-in');
        Route::post('/timekeeping/clock-out', [DriverController::class, 'clockOut'])->name('driver.timekeeping.clock-out');
        Route::post('/status', [DriverController::class, 'updateStatus'])->name('driver.status.update');
    });

    Route::get('/fare/{id}', [FareController::class, 'view'])->middleware('role:admin')->name('fares.view');
    Route::get('/fares', [FareController::class, 'index'])->middleware('role:admin')->name('fares.index');
    Route::put('/fare/upload', [FareController::class, 'upload'])->middleware('role:admin')->name('fares.upload');
    Route::put('/fare/update', [FareController::class, 'bulkUpdate'])->middleware('role:admin')->name('fares.bulk-update');
    Route::delete('/fare/{id}/delete', [FareController::class, 'delete'])->middleware('role:admin')->name('fares.destroy');

    Route::post('/driver/dev/markers', [DevMarkerController::class, 'store'])->name('driver.dev.add-marker');
    Route::post('/driver/dev/markers/{marker}/toggle', [DevMarkerController::class, 'toggle'])->name('driver.dev.toggle-marker');
    Route::delete('/driver/dev/markers/{marker}', [DevMarkerController::class, 'remove'])->name('driver.dev.remove-marker');
    Route::delete('/driver/dev/markers', [DevMarkerController::class, 'clear'])->name('driver.dev.clear-markers');

    Route::resource('users', UserController::class);
    Route::resource('routes', RouteController::class)->middleware('role:admin');
    Route::resource('rates', RateController::class)->middleware('role:admin');
});
