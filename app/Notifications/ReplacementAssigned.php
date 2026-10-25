<?php

namespace App\Notifications;

use App\Models\Driver;
use App\Models\DriverVacancy;
use App\Models\Vehicle;
use Illuminate\Notifications\Messages\MailMessage;

/** To the replacement driver: the system put them on a vehicle that had lost its driver. */
class ReplacementAssigned extends AppNotification
{
    public function __construct(public Vehicle $vehicle, public DriverVacancy $vacancy, public ?Driver $coDriver = null)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('You have been assigned to '.$this->vehicle->plate_number)
            ->greeting($this->greetingFor($notifiable))
            ->line("You have been assigned to vehicle {$this->vehicle->plate_number} ({$this->vehicle->brand} {$this->vehicle->model}), which needed a driver.");

        if ($this->coDriver) {
            $next = $notifiable->driver?->nextDutyDate();

            $mail->line("You share it with {$this->coDriver->name}; you take turns day by day.")
                ->line($next ? 'Your next duty day is '.$next->format('l, M j').'.' : 'Check the duty roster for your days.');
        } else {
            $mail->line('It is yours to drive; you can clock in on your next shift.');
        }

        return $mail->line('If this is a mistake, tell the Maintenance Manager.')
            ->action('Open your dashboard', route('dashboard'));
    }
}
