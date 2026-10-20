<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * UCN_SC_E012 / E018 — a suspension takes effect immediately.
 *
 * Sign-in already refuses a suspended account, but a session that was opened
 * before the suspension must not keep working. Every authenticated request
 * re-checks the account and, if it is suspended, ends the session.
 */
class EnsureAccountActive
{
    private const MESSAGE = 'This account has been suspended. Please contact the administrator.';

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->isSuspended()) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => self::MESSAGE,
                'redirect' => route('login'),
            ], 403);
        }

        return redirect()->route('login')->withErrors(['credentials' => self::MESSAGE]);
    }
}
