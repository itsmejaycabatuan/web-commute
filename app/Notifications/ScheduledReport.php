<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

/** To the Admins: the weekly or monthly report, with its figures (and optionally the audit trail) as CSV. */
class ScheduledReport extends AppNotification
{
    /**
     * @param  array<string, string>  $attachments  file name => CSV contents
     */
    public function __construct(public string $kind, public array $report, public array $attachments = [])
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $r = $this->report;

        $mail = (new MailMessage)
            ->subject(ucfirst($this->kind).' report: '.$r['label'])
            ->greeting($this->greetingFor($notifiable))
            ->line("Here is the {$this->kind} SmartCommute report for {$r['label']}.")
            ->line('**Money**')
            ->line('- Fare revenue: '.$this->peso($r['revenue']['total']).' from '.$r['revenue']['count'].' paid fares')
            ->line('- Wallet top-ups: '.$this->peso($r['topups']['total']).' ('.$r['topups']['count'].')')
            ->line('- Failed payments: '.$r['payments']['failed'])
            ->line('**Drivers**')
            ->line('- Hours worked: '.number_format($r['time']['hours'], 1).' ('.number_format($r['time']['overtime'], 1).' overtime)')
            ->line('- Shifts closed automatically: '.$r['time']['auto_closed'].' ('.$r['time']['awaiting_review'].' still awaiting review)')
            ->line('- Violations logged: '.$r['violations']['count'].', fines issued '.$this->peso($r['violations']['issued']).', collected '.$this->peso($r['violations']['collected']).', outstanding '.$this->peso($r['violations']['outstanding']))
            ->line('**Fleet**')
            ->line('- Distance driven: '.number_format($r['distance_km'], 1).' km')
            ->line('- Maintenance spend: '.$this->peso($r['maintenance']['cost']).' ('.$r['maintenance']['services'].' services); '.$r['maintenance']['overdue_now'].' overdue and '.$r['maintenance']['due_soon_now'].' due soon right now');

        if ($r['repeat_violators']) {
            $mail->line('**Repeat violators:** '.collect($r['repeat_violators'])->map(fn ($v) => "{$v['name']} ({$v['count']})")->implode(', '));
        }
        if ($r['abandoned_scans']) {
            $mail->line('**Scans never paid for:** '.collect($r['abandoned_scans'])->map(fn ($v) => "{$v['email']} ({$v['count']})")->implode(', '));
        }

        foreach ($this->attachments as $name => $csv) {
            $mail->attachData($csv, $name, ['mime' => 'text/csv']);
        }

        return $mail->action('Open fleet analytics', route('analytics.index'));
    }
}
