<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * The one way the application sends a notification. A mail server that is down or a mailbox that
 * bounces must never fail the request or job that triggered the message, so every send is guarded and
 * failures go to the log instead.
 */
class Notifier
{
    /**
     * @param  User|iterable<User>|null  $notifiables
     * @return int  how many people the message was handed to
     */
    public static function send($notifiables, Notification $notification): int
    {
        if ($notifiables === null) {
            return 0;
        }

        $sent = 0;

        foreach (self::toList($notifiables) as $user) {
            if (blank($user->email)) {
                continue;
            }

            try {
                $user->notify($notification);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Notification could not be sent.', [
                    'notification' => class_basename($notification),
                    'user_id' => $user->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    /** Everyone holding one of the roles who can still sign in. */
    public static function toRole(string|array $roles, Notification $notification): int
    {
        return self::send(self::withRole($roles), $notification);
    }

    /** @return Collection<int, User> */
    public static function withRole(string|array $roles): Collection
    {
        return User::role($roles)->where('is_suspended', false)->get();
    }

    private static function toList($notifiables): Collection
    {
        return $notifiables instanceof User ? collect([$notifiables]) : collect($notifiables);
    }
}
