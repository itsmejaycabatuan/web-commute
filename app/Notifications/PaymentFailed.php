<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;

/** To the commuter: a fare payment did not go through. Nothing was charged. */
class PaymentFailed extends AppNotification
{
    public function __construct(public Payment $payment)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $p = $this->payment;

        return (new MailMessage)
            ->subject('Your fare payment did not go through')
            ->greeting($this->greetingFor($notifiable))
            ->line('Your fare payment '.$p->transaction_id.' ('.$this->peso($p->price).' via '.$p->payment_method.') could not be completed.')
            ->line('Nothing was charged. You can start a new payment from the map.')
            ->action('See your payments', route('payment.history'));
    }
}
