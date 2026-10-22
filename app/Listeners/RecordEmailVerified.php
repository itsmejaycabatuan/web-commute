<?php

namespace App\Listeners;

use App\Models\EmailVerificationCode;
use App\Support\Audit;
use Illuminate\Auth\Events\Verified;

/**
 * Runs when an email is verified, by the emailed link or by the 6-digit code: the code is spent either
 * way, and the audit trail says which method was used.
 */
class RecordEmailVerified
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        EmailVerificationCode::where('user_id', $user->id)->delete();

        Audit::log(Audit::ACCOUNT, 'email_verified', 'Verified email '.$user->email, $user, [
            'method' => request()->routeIs('verification.code') ? 'code' : 'link',
        ], $user);
    }
}
