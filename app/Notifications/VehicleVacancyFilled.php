<?php

namespace App\Notifications;

use App\Models\DriverVacancy;
use Illuminate\Notifications\Messages\MailMessage;

/** To the Maintenance Managers: a vehicle that lost its driver has a replacement. */
class VehicleVacancyFilled extends AppNotification
{
    public function __construct(public DriverVacancy $vacancy)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $v = $this->vacancy;
        $plate = $v->vehicle?->plate_number ?? 'a vehicle';

        return (new MailMessage)
            ->subject("{$plate} has a new driver: ".($v->replacementDriver?->name ?? 'replacement'))
            ->greeting($this->greetingFor($notifiable))
            ->line(($v->formerDriver?->name ?? 'A driver')." left {$plate} ({$v->reasonLabel()}), so the system assigned ".($v->replacementDriver?->name ?? 'a replacement').' automatically.')
            ->line('If the vehicle is now shared, a 30-day alternating duty roster was generated; you can change it on the Duty Roster page.')
            ->action('See the vehicles', route('vehicles.index'));
    }
}
