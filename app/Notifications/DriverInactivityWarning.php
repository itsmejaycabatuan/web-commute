<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a driver who has not clocked in for several days: they must email HR about the
 * inactivity, or the account will be suspended automatically.
 */
class DriverInactivityWarning extends Notification
{
    public function __construct(public int $daysInactive, public int $suspendAfterDays, public ?string $hrEmail = null)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $hr = $this->hrEmail ? "HR at {$this->hrEmail}" : 'HR';

        return (new MailMessage)
            ->subject('Action needed: you have not clocked in for '.$this->daysInactive.' days')
            ->greeting('Hello '.($notifiable->name ?? 'driver').',')
            ->line("You have not clocked in for {$this->daysInactive} days.")
            ->line("Please email {$hr} to explain the inactivity.")
            ->line("If you stay inactive for more than {$this->suspendAfterDays} days, your driver account will be suspended automatically and you will no longer be able to sign in or clock in.")
            ->line('Clocking in again clears this warning.');
    }
}
