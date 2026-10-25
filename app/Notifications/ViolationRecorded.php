<?php

namespace App\Notifications;

use App\Models\ViolationLog;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;

/** To the driver: a violation was logged against them. */
class ViolationRecorded extends AppNotification
{
    public function __construct(public ViolationLog $violation)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $v = $this->violation->loadMissing('violationCode');
        $date = Carbon::parse($v->date_of_violation)->format('M d, Y');

        return (new MailMessage)
            ->subject('A violation was recorded: '.($v->violationCode?->violation_name ?? 'violation'))
            ->greeting($this->greetingFor($notifiable))
            ->line('A violation was recorded against your driver account.')
            ->line('**'.($v->violationCode?->code ?? 'N/A').' — '.($v->violationCode?->violation_name ?? 'Unknown').'**')
            ->line('Offense: '.$this->ordinal((int) $v->violation_instance))
            ->line('When and where: '.$date.', '.Carbon::parse($v->time_of_violation)->format('h:i A').', '.$v->place_of_violation)
            ->line('Fine: '.$this->peso($v->violation_fine))
            ->when($v->remarks, fn ($m) => $m->line('Remarks: '.$v->remarks))
            ->action('See your violations', route('driver.violations'));
    }

    private function ordinal(int $n): string
    {
        return match (true) {
            $n <= 1 => '1st',
            $n === 2 => '2nd',
            $n === 3 => '3rd',
            default => '4th or later',
        };
    }
}
