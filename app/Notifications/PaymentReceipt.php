<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Notifications\Messages\MailMessage;

/** To the commuter: the receipt for a fare that was paid. */
class PaymentReceipt extends AppNotification
{
    public function __construct(public Payment $payment)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $p = $this->payment;

        return (new MailMessage)
            ->subject('Receipt for your fare '.$p->transaction_id)
            ->greeting($this->greetingFor($notifiable))
            ->line('Thank you. Your fare was paid.')
            ->line('Reference: '.$p->transaction_id)
            ->line('Trip: '.$p->starting_point.' → '.$p->destination.' ('.number_format((float) $p->total_distance, 1).' km)')
            ->line('Paid with: '.$p->payment_method)
            ->line('Amount: '.$this->peso($p->price))
            ->line('Paid on: '.($p->paid_at ?? now())->format('M d, Y h:i A'))
            ->action('View receipt', route('payment.showReceipt', $p->id));
    }
}
