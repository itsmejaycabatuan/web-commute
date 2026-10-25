<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

/** To the Driver Managers: drivers who piled up violations in a short time. */
class RepeatViolatorFlag extends AppNotification
{
    /** @param  Collection  $rows  FleetAnalyticsService::repeatViolators() rows */
    public function __construct(public Collection $rows, public int $days)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Repeat violators: '.$this->rows->count().' driver'.($this->rows->count() === 1 ? '' : 's').' flagged')
            ->greeting($this->greetingFor($notifiable))
            ->line("These drivers have {$this->rows->first()['count']} or more violations in the last {$this->days} days and may need a conversation or a suspension:");

        foreach ($this->rows as $row) {
            $codes = $row['violations']->map(fn ($v) => $v->violationCode?->code)->filter()->countBy()->map(fn ($n, $code) => $n > 1 ? "{$code} ×{$n}" : $code)->implode(', ');
            $mail->line('- **'.$row['driver']->name.'** ('.($row['driver']->driver_code ?: '#'.$row['driver']->id).'): '.$row['count'].' violations, '.$this->peso($row['fines']).' in fines ('.$codes.')');
        }

        return $mail->action('Open the violations log', route('driver-manager.violations-log'));
    }
}
