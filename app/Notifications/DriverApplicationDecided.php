<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/** To the driver: their application was approved or rejected. */
class DriverApplicationDecided extends AppNotification
{
    public function __construct(public bool $approved)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        if ($this->approved) {
            return (new MailMessage)
                ->subject('Your driver account was approved')
                ->greeting($this->greetingFor($notifiable))
                ->line('Your driver application was approved. You can sign in and start your shifts.')
                ->action('Sign in', route('login'));
        }

        return (new MailMessage)
            ->subject('Your driver application was not approved')
            ->greeting($this->greetingFor($notifiable))
            ->line('Your driver application was not approved, so you cannot sign in as a driver yet.')
            ->line('If you think this is a mistake, contact the administrator.');
    }
}
