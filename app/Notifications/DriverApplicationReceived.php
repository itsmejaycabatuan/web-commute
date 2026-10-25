<?php

namespace App\Notifications;

use App\Models\Driver;
use Illuminate\Notifications\Messages\MailMessage;

/** To the Admins: a driver registered and is waiting for approval. */
class DriverApplicationReceived extends AppNotification
{
    public function __construct(public Driver $driver, public string $email)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New driver application: '.$this->driver->name)
            ->greeting($this->greetingFor($notifiable))
            ->line("{$this->driver->name} ({$this->email}) registered as a driver and is waiting for approval.")
            ->line('Check the licence image and enter the licence number, code and expiry date to approve the account.')
            ->action('Review applications', route('drivers.index'));
    }
}
