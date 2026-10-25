<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

/** To the Admins: commuters who keep scanning the ride QR code and not paying. */
class FareEvasionFlag extends AppNotification
{
    /** @param  Collection  $rows  FleetAnalyticsService::abandonedScans() rows */
    public function __construct(public Collection $rows, public int $days)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Possible fare evasion: '.$this->rows->count().' commuter'.($this->rows->count() === 1 ? '' : 's').' flagged')
            ->greeting($this->greetingFor($notifiable))
            ->line("These commuters scanned a ride QR code and did not pay a fare afterwards, repeatedly, in the last {$this->days} days:");

        foreach ($this->rows as $row) {
            $mail->line('- **'.$row['email'].'**: '.$row['count'].' unpaid scans (latest '.$row['last']->format('M d, h:i A').')');
        }

        return $mail
            ->line('A scan is only a sign of intent to ride: check the account before taking any action.')
            ->action('See fare transactions', route('faretransactions'));
    }
}
