<?php

namespace App\Notifications;

use App\Models\TopupHistory;
use Illuminate\Notifications\Messages\MailMessage;

/** To the commuter: their wallet was topped up. */
class TopupReceipt extends AppNotification
{
    public function __construct(public TopupHistory $topup, public float $newBalance)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $t = $this->topup;

        return (new MailMessage)
            ->subject('Your wallet was topped up: '.$this->peso($t->amount_added))
            ->greeting($this->greetingFor($notifiable))
            ->line('Your top-up was completed.')
            ->line('Amount added: '.$this->peso($t->amount_added))
            ->line('Paid with: '.strtoupper((string) $t->payment_method))
            ->line('New balance: '.$this->peso($this->newBalance))
            ->action('See your top-ups', route('payment.topup.history'));
    }
}
