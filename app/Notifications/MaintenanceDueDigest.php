<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

/**
 * The Maintenance Manager's daily list of services that are overdue or about to fall due.
 */
class MaintenanceDueDigest extends AppNotification
{
    /** @param  Collection  $fleet  the result of MaintenanceDueService::fleet() */
    public function __construct(public Collection $fleet)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $overdue = $this->fleet->sum(fn ($row) => $row['overdue']->count());
        $soon = $this->fleet->sum(fn ($row) => $row['due_soon']->count());

        $mail = (new MailMessage)
            ->subject("Maintenance due: {$overdue} overdue, {$soon} due soon")
            ->line("{$overdue} overdue and {$soon} due-soon preventive maintenance service(s) across your fleet.");

        foreach ($this->fleet as $row) {
            $mail->line('**'.$row['vehicle']->plate_number.'**');

            foreach ($row['overdue'] as $item) {
                $mail->line('- OVERDUE: '.$this->describe($item));
            }
            foreach ($row['due_soon'] as $item) {
                $mail->line('- Due soon: '.$this->describe($item));
            }
        }

        return $mail->action('Open preventive maintenance', route('maintenance-manager.preventive-maintenance'));
    }

    private function describe(array $item): string
    {
        $parts = [];

        if ($item['due_date']) {
            $parts[] = 'by '.$item['due_date'];
        }
        if ($item['due_odo'] !== null) {
            $parts[] = 'at '.number_format($item['due_odo']).' km'
                .($item['km_left'] !== null ? ($item['km_left'] >= 0 ? ' ('.number_format($item['km_left']).' km left)' : ' ('.number_format(-$item['km_left']).' km past)') : '');
        }

        return $item['task'].($parts ? ' — '.implode(', ', $parts) : '');
    }
}
