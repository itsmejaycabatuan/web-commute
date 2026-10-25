<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Tells "this person's session timed out" apart from "this person was never signed in".
 *
 * Laravel drops an idle session after `session.lifetime` minutes, and the session cookie
 * expires with it, so by the time the user comes back the server cannot tell the two cases
 * apart. A small long-lived flag cookie is set at sign-in and removed at sign-out; a guest
 * who still carries it must have been signed out by the timeout, and is told so.
 */
class SessionExpiry
{
    public const COOKIE = 'sc_signed_in';

    public const EXPIRED_MESSAGE = 'You were signed out because you were inactive for a while. Please sign in again.';

    public const PAGE_EXPIRED_MESSAGE = 'Your page expired. Please try again.';

    public static function markSignedIn(): void
    {
        Cookie::queue(Cookie::make(self::COOKIE, '1', 60 * 24 * 30));
    }

    public static function clear(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE));
    }

    public static function wasSignedIn(Request $request): bool
    {
        return $request->cookie(self::COOKIE) !== null;
    }
}
