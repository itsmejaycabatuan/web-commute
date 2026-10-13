<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureDriverApproved
{
    /**
     * UCN_SC_E022 — a driver whose registration is still pending or was rejected
     * is signed out again, on every driver route and not only on the map.
     *
     * A driver-role user with no Driver row is let through: the controllers
     * answer that case themselves ("Driver profile not found.").
     */
    public function handle(Request $request, Closure $next)
    {
        $driver = $request->user()?->driver;

        if (! $driver || ($driver->is_approved && ! $driver->is_rejected)) {
            return $next($request);
        }

        $flag = $driver->is_rejected ? 'driver_rejected' : 'driver_pending';

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with($flag, true);
    }
}
