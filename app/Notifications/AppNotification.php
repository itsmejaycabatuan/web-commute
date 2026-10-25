<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Base of every message the system sends by itself. Channels come from config('alerts.channels'), so
 * adding push or SMS later is a configuration change plus one `toX()` method, not a change per message.
 */
abstract class AppNotification extends Notification
{
    public function via($notifiable): array
    {
        return config('alerts.channels', ['mail']);
    }

    /** "Hello Ramon," — a driver's name, otherwise the account's email (accounts have no name). */
    protected function greetingFor($notifiable): string
    {
        $name = $notifiable->driver?->name ?? $notifiable->name ?? $notifiable->email ?? null;

        return 'Hello'.($name ? ' '.$name : '').',';
    }

    protected function peso($amount): string
    {
        return '₱'.number_format((float) $amount, 2);
    }
}
