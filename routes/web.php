    <?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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


    Route::get('/register', function () {
        return view('register');
    })->name('register');

    Route::get('/login',function (){
        return view('login');
    })->name('login');

    Route::get('/dashboard/commuter', function () {
        return view('commuter.dashboard');
    })->name('commuter.dashboard');

    Route::get('/commuter/commuter', function () {
        return view('commuter.commuter');
    })->name('commuter.commuter');

    Route::get('/profile', function () {
        return view('profile');
    })->name('profile');
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
    $request->fulfill();    
    return view('commuter.dashboard');
})->middleware(['auth','signed'])->name('verification.verify');

Route::get('/forgot-password', function() {
    return view('auth.forgot-password');
})->middleware('guest')->name('password.request');

Route::post('/forgot-password', function(Request $request) {
    $request->validate(['email' => 'required|email']);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    return $status === Password::RESET_LINK_SENT ? back()->with(['status' => __($status)]) : back()->withErrors(['email' => __($status)]);
})->middleware('guest')->name('password.email');

Route::get('/reset-password/{token}', function(string $token) {
    return view('auth.reset-password', ['token' => $token]);
})->middleware('guest')->name('password.reset');

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
})->middleware('guest')->name('password.update');

Route::middleware(['auth', 'verified'])->group(function (){

    Route::get('/dashboard/commuter', function () {
        return view('commuter.dashboard');
    })->name('commuter.dashboard');

    Route::get('/commuter/commuter', function () {
        return view('commuter.commuter');
    })->name('commuter.commuter');

    Route::resource('users', UserController::class);
});




