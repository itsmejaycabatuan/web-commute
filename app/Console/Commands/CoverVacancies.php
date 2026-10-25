<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Models\DriverVacancy;
use App\Services\ReplacementDriverService;
use Illuminate\Console\Command;

class CoverVacancies extends Command
{
    protected $signature = 'vehicles:cover-vacancies {--dry-run : List what would happen without changing or emailing anything}';

    protected $description = 'Take drivers with an expired licence off their vehicle, fill vehicles that lost a driver with a free driver, and report what is still unfilled';

    public function handle(ReplacementDriverService $replacements): int
    {
        if ($this->option('dry-run')) {
            return $this->dryRun($replacements);
        }

        $released = config('fleet.release_expired_licences') ? $replacements->releaseExpiredLicences() : collect();
        $retried = config('fleet.auto_replace') ? $replacements->retryOpen() : ['filled' => 0, 'still_open' => DriverVacancy::open()->count()];
        $reminded = $replacements->remindOpen();

        $this->info("Released {$released->count()} driver(s) with an expired licence, filled {$retried['filled']} vacancy(ies), {$retried['still_open']} still open ({$reminded} reported).");

        return self::SUCCESS;
    }

    private function dryRun(ReplacementDriverService $replacements): int
    {
        $expired = Driver::where('is_approved', true)->where('is_suspended', false)->whereHas('vehicle')->with('vehicle')->get()
            ->filter(fn (Driver $d) => $d->licenseState() === 'expired');

        foreach ($expired as $driver) {
            $this->line("{$driver->name}: licence expired — would leave ".$driver->vehicle->pluck('plate_number')->implode(', '));
        }

        foreach (DriverVacancy::open()->with('vehicle')->get() as $vacancy) {
            $this->line(($vacancy->vehicle?->plate_number ?? '?').": open since {$vacancy->opened_at->toDateTimeString()} ({$vacancy->reasonLabel()})");
        }

        $this->line('Free drivers: '.($replacements->candidates()->map(fn ($d) => $d->name)->implode(', ') ?: 'none'));

        return self::SUCCESS;
    }
}
