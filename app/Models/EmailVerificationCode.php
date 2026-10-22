<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * The 6-digit alternative to the emailed verification link (UCN_SC_E002 A2).
 *
 * One live code per user; issuing a new one replaces the old. Only a hash is stored. A code lasts as
 * long as the link (auth.verification.expire, 60 minutes) and is thrown away after MAX_ATTEMPTS wrong
 * guesses, so it cannot be brute-forced.
 */
class EmailVerificationCode extends Model
{
    public const LENGTH = 6;
    public const MAX_ATTEMPTS = 5;

    public const OK = 'ok';
    public const INVALID = 'invalid';
    public const EXPIRED = 'expired';
    public const LOCKED = 'locked';
    public const MISSING = 'missing';

    protected $fillable = ['user_id', 'code_hash', 'attempts', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    /** Create (or replace) the user's code and return the plain code to email. */
    public static function issue(User $user): string
    {
        $code = str_pad((string) random_int(0, 10 ** self::LENGTH - 1), self::LENGTH, '0', STR_PAD_LEFT);

        static::updateOrCreate(['user_id' => $user->id], [
            'code_hash' => Hash::make($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes((int) config('auth.verification.expire', 60)),
        ]);

        return $code;
    }

    /** Check a typed code. A correct code is consumed; the caller marks the email verified. */
    public static function attempt(User $user, string $code): string
    {
        return DB::transaction(function () use ($user, $code) {
            $row = static::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $row) {
                return self::MISSING;
            }

            if ($row->expires_at->isPast()) {
                $row->delete();

                return self::EXPIRED;
            }

            if (Hash::check($code, $row->code_hash)) {
                $row->delete();

                return self::OK;
            }

            $row->increment('attempts');

            if ($row->attempts >= self::MAX_ATTEMPTS) {
                $row->delete();

                return self::LOCKED;
            }

            return self::INVALID;
        });
    }
}
