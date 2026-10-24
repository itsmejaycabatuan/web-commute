<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Notifications\DriverInactivityWarning;
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DriverInactivityCheck extends Command
{
    protected $signature = 'drivers:inactivity-check {--dry-run : List what would happen without emailing or suspending anyone}';

    protected $description = 'Email drivers who have not clocked in for a few days (to contact HR) and suspend those inactive for over a week';

    public function handle(): int
    {
        $warnAfter = (int) config('timekeeping.inactivity_warn_after_days', 3);
        $suspendAfter = (int) config('timekeeping.inactivity_suspend_after_days', 7);
        $dry = (bool) $this->option('dry-run');

        $warned = 0;
        $suspended = 0;

        $drivers = Driver::with('user')
            ->where('is_approved', true)
            ->where('is_rejected', false)
            ->where('is_suspended', false)
            ->get();

        foreach ($drivers as $driver) {
            $days = $driver->daysInactive();

            // Back on the road: the next lapse starts a fresh warning.
            if ($days < $warnAfter) {
                if ($driver->inactivity_warned_at && ! $dry) {
                    $driver->forceFill(['inactivity_warned_at' => null])->save();
                }

                continue;
            }

            // Suspend only a driver who has had the warning for at least a day.
            if ($days > $suspendAfter && $driver->inactivity_warned_at && $driver->inactivity_warned_at->lt(today())) {
                $this->line("{$this->label($driver)}: inactive {$days} days — suspend");

                if (! $dry && $this->suspendDriver($driver, $days)) {
                    $suspended++;
                }

                continue;
            }

            if (! $driver->inactivity_warned_at) {
                $this->line("{$this->label($driver)}: inactive {$days} days — warn");

                if (! $dry && $this->sendWarning($driver, $days, $suspendAfter)) {
                    $warned++;
                }
            }
        }

        $this->info("Warned {$warned} driver(s), suspended {$suspended}.");

        return self::SUCCESS;
    }

    private function sendWarning(Driver $driver, int $days, int $suspendAfter): bool
    {
        if (! $driver->user) {
            return false;
        }

        try {
            $driver->user->notify(new DriverInactivityWarning($days, $suspendAfter, config('timekeeping.hr_email')));
        } catch (\Throwable $e) {
            // Not marked as warned, so tomorrow's run tries again; one bad mailbox must not stop the rest.
            Log::error('Driver inactivity warning could not be sent.', ['driver_id' => $driver->id, 'exception' => $e->getMessage()]);

            return false;
        }

        $driver->forceFill(['inactivity_warned_at' => now()])->save();

        Audit::log(Audit::DRIVER, 'inactivity_warned', 'Warned driver '.$this->label($driver).' about '.$days.' days without clocking in', $driver, [
            'days_inactive' => $days,
        ]);

        return true;
    }

    private function suspendDriver(Driver $driver, int $days): bool
    {
        $driver->suspend("Automatically suspended: inactive for {$days} days");

        Audit::log(Audit::DRIVER, 'suspended', 'Automatically suspended driver '.$this->label($driver).' after '.$days.' days without clocking in', $driver, [
            'email' => $driver->user?->email,
            'reason' => $driver->suspension_reason,
            'days_inactive' => $days,
            'automatic' => true,
        ]);

        return true;
    }

    private function label(Driver $driver): string
    {
        return $driver->driver_code ?: '#'.$driver->id;
    }
}
