<?php

namespace App\Listeners;

use App\Support\SessionExpiry;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Events\Dispatcher;

/** Keeps the SessionExpiry flag cookie in step with signing in and out. */
class TrackSignedInFlag
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => fn () => SessionExpiry::markSignedIn(),
            Logout::class => fn () => SessionExpiry::clear(),
        ];
    }
}
