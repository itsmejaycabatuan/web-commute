<?php

namespace App\Notifications;

use App\Models\TopupHistory;
use Illuminate\Notifications\Messages\MailMessage;

/** To the commuter: a wallet top-up did not go through. Nothing was charged and the balance is unchanged. */
class TopupFailed extends AppNotification
{
    public function __construct(public TopupHistory $topup)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $t = $this->topup;

        return (new MailMessage)
            ->subject('Your top-up did not go through')
            ->greeting($this->greetingFor($notifiable))
            ->line('Your top-up '.$t->transaction_id.' ('.$this->peso($t->amount_added).' via '.strtoupper((string) $t->payment_method).') could not be completed.')
            ->line('Nothing was charged and your balance is unchanged. You can try again whenever you like.')
            ->action('Top up again', route('payment.topup'));
    }
}
