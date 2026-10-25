<?php

namespace App\Notifications;

use App\Models\ViolationLog;
use Illuminate\Notifications\Messages\MailMessage;

/** To the driver: a fine was marked as paid. */
class ViolationPaid extends AppNotification
{
    public function __construct(public ViolationLog $violation)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $v = $this->violation->loadMissing('violationCode');

        return (new MailMessage)
            ->subject('Your fine was marked as paid')
            ->greeting($this->greetingFor($notifiable))
            ->line('The fine for '.($v->violationCode?->violation_name ?? 'your violation').' ('.$this->peso($v->violation_fine).') was marked as paid.')
            ->line('It no longer counts toward your outstanding balance.')
            ->action('See your violations', route('driver.violations'));
    }
}
