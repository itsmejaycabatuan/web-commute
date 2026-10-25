<?php

namespace App\Notifications;

use App\Models\Driver;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

/** To the driver: their licence expired, so they were taken off their vehicle. */
class LicenceExpiredRelease extends AppNotification
{
    public function __construct(public Driver $driver, public Collection $vehicles)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $date = $this->driver->expiration_date ? \Illuminate\Support\Carbon::parse($this->driver->expiration_date)->format('M j, Y') : 'recently';

        return (new MailMessage)
            ->subject("You were taken off {$this->vehicles->pluck('plate_number')->implode(', ')}: your licence expired")
            ->greeting($this->greetingFor($notifiable))
            ->line("Your driver's licence expired on {$date}, so you cannot drive until it is renewed. You were taken off {$this->vehicles->pluck('plate_number')->implode(', ')} and another driver may take it over.")
            ->line('Renew your licence, ask the administrator to update the expiry date, then ask the Maintenance Manager for a vehicle.');
    }
}
