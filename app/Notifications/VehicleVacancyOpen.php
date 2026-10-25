<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Collection;

/** To the Maintenance Managers: vehicles that are short of a driver, and who could fill them. */
class VehicleVacancyOpen extends AppNotification
{
    /**
     * @param  Collection  $vacancies  open DriverVacancy rows (vehicle and former driver loaded)
     * @param  Collection  $suggested  the best free drivers right now (may be empty)
     */
    public function __construct(public Collection $vacancies, public Collection $suggested)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $count = $this->vacancies->count();

        $mail = (new MailMessage)
            ->subject($count === 1 ? ($this->vacancies->first()->vehicle?->plate_number ?? 'A vehicle').' needs a driver' : "{$count} vehicles need a driver")
            ->greeting($this->greetingFor($notifiable))
            ->line('These vehicles are short of a driver:');

        foreach ($this->vacancies as $vacancy) {
            $mail->line('- **'.($vacancy->vehicle?->plate_number ?? 'Unknown').'**: '.($vacancy->formerDriver?->name ?? 'a driver').' left ('.$vacancy->reasonLabel().') '.$vacancy->opened_at->diffForHumans());
        }

        if ($this->suggested->isNotEmpty()) {
            $mail->line('Free drivers who could take one: '.$this->suggested->map(fn ($d) => $d->name.' ('.$d->driver_code.')')->implode(', ').'.');

            if (! config('fleet.auto_replace')) {
                $mail->line('Automatic replacement is switched off, so assign one yourself.');
            }
        } else {
            $mail->line('No free driver is available. Approve a new driver, or free one up, and the system will assign them on its next hourly check.');
        }

        return $mail->action('Open the vehicles', route('vehicles.index'));
    }
}
