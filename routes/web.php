<?php

use App\Http\Controllers\Admin\CommuterController;
use App\Http\Controllers\Admin\DriverApprovalController;
use App\Http\Controllers\DevMarkerController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverManagerController;
use App\Http\Controllers\FareController;
use App\Http\Controllers\MaintenanceManagerController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PusherController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleTrackingController;
use App\Models\Fare;
use App\Models\FareRate;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
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
    return view('home');
})->name('home');

Route::get('/pusher', [PusherController::class, 'index'])->name('pusher.index');
Route::post('/fire-event', [PusherController::class, 'fireEvent'])->name('fire.event');

Route::post('/track/vehicle/broadcast', [VehicleTrackingController::class, 'broadcastLocation'])->name('vehicle.broadcast');
Route::get('/track/vehicles/active', [VehicleTrackingController::class, 'getActiveVehicles']);

Route::get('/map', [UserController::class, 'map'])->name('map');

Route::middleware('guest')->group(function () {
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/register', [UserController::class, 'register'])->name('users.register');
    Route::post('/login', [UserController::class, 'login'])->name('users.login');

    Route::get('/register/driver', [DriverController::class, 'create'])->name('driver.register.page');
    Route::post('/register/driver', [DriverController::class, 'store'])->name('driver.register');

    Route::get('/forgot-password', [UserController::class, 'forgotPassword'])->name('password.request');
    Route::post('/forgot-password', [UserController::class, 'requestPassword'])->name('password.email');
    Route::get('/reset-password/{token}', [UserController::class, 'resetPassword'])->name('password.reset');
    Route::post('/reset-password', [UserController::class, 'updatePassword'])->name('password.update');
});

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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/logout', [UserController::class, 'logout'])->name('users.logout');

    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');

    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
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
    Route::get('/transactions/receipt/{id}', [PaymentController::class, 'showReceiptAdmin'])->name('admin.receipt.show');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/logout-others', [SettingsController::class, 'logoutOtherDevices'])->name('settings.logout-others');
    Route::patch('/settings/theme', [UserPreferenceController::class, 'updateTheme'])->name('settings.update.theme');
    Route::patch('/settings/font-size', [UserPreferenceController::class, 'updateFontSize'])->name('settings.update.fontsize');
    Route::post('/settings/export-data', [SettingsController::class, 'exportData'])->name('settings.export-data');

    Route::middleware('role:admin|driver_manager')->group(function () {
        Route::get('/drivers', [DriverApprovalController::class, 'index'])->name('drivers.index');
        Route::post('/drivers', [DriverApprovalController::class, 'store'])->name('drivers.store');
        Route::get('/drivers/create', [DriverApprovalController::class, 'create'])->name('drivers.create');
        Route::get('/drivers/{user}/edit', [DriverApprovalController::class, 'edit'])->name('drivers.edit');
        Route::put('/drivers/{user}', [DriverApprovalController::class, 'update'])->name('drivers.update');
        Route::delete('/drivers/{driver}', [DriverApprovalController::class, 'destroy'])->name('drivers.destroy');
        Route::get('/drivers/{user}/license', [DriverApprovalController::class, 'showLicense'])->name('drivers.license');
        Route::post('/drivers/{user}/approve', [DriverApprovalController::class, 'approve'])->name('drivers.approve');
        Route::put('/drivers/{user}/reject', [DriverApprovalController::class, 'reject'])->name('drivers.reject');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/commuters/create', [CommuterController::class, 'create'])->name('commuters.create');
        Route::post('/commuters', [CommuterController::class, 'store'])->name('commuters.store');
        Route::get('/commuters/{user}/edit', [CommuterController::class, 'edit'])->name('commuters.edit');
        Route::put('/commuters/{user}', [CommuterController::class, 'update'])->name('commuters.update');
        Route::delete('/commuters/{user}', [CommuterController::class, 'destroy'])->name('commuters.destroy');
        Route::get('/commuters', [CommuterController::class, 'index'])->name('commuters.index');

        Route::get('/fare/{id}', [FareController::class, 'view'])->name('fares.view');
        Route::get('/fares', [FareController::class, 'index'])->name('fares.index');
        Route::put('/fare/upload', [FareController::class, 'upload'])->name('fares.upload');
        Route::put('/fare/update', [FareController::class, 'bulkUpdate'])->name('fares.bulk-update');
        Route::delete('/fare/{id}/delete', [FareController::class, 'delete'])->name('fares.destroy');

        Route::resource('routes', RouteController::class);
        Route::resource('rates', RateController::class);
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
        Route::get('/maintenance-logs', [MaintenanceManagerController::class, 'maintenanceLogs'])->name('maintenance-manager.maintenance-logs');
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

    Route::post('/driver/dev/markers', [DevMarkerController::class, 'store'])->name('driver.dev.add-marker');
    Route::post('/driver/dev/markers/{marker}/toggle', [DevMarkerController::class, 'toggle'])->name('driver.dev.toggle-marker');
    Route::delete('/driver/dev/markers/{marker}', [DevMarkerController::class, 'remove'])->name('driver.dev.remove-marker');
    Route::delete('/driver/dev/markers', [DevMarkerController::class, 'clear'])->name('driver.dev.clear-markers');

    Route::delete('/delete-account', [UserController::class, 'destroy'])->name('users.delete-account');

    // Route::resource('users', UserController::class);
});
