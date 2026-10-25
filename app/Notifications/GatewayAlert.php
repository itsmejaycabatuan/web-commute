<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/** To the Admins: the payment gateway keeps failing. */
class GatewayAlert extends AppNotification
{
    public function __construct(public int $failures, public int $windowMinutes, public string $lastContext)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment gateway problem: '.$this->failures.' failures in '.$this->windowMinutes.' minutes')
            ->greeting($this->greetingFor($notifiable))
            ->error()
            ->line("The payment gateway failed {$this->failures} times in the last {$this->windowMinutes} minutes.")
            ->line('Most recent: '.$this->lastContext)
            ->line('Commuters paying with GCash or Maya may be unable to complete a fare. Check the PayMongo status page and the application log.')
            ->action('See fare transactions', route('faretransactions'));
    }
}
