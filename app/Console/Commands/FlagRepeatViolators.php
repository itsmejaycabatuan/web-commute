<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Notifications\RepeatViolatorFlag;
use App\Services\FleetAnalyticsService;
use App\Support\Audit;
use App\Support\Notifier;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class FlagRepeatViolators extends Command
{
    protected $signature = 'drivers:flag-repeat-violators {--dry-run : List the drivers without emailing or logging}';

    protected $description = 'Tell the Driver Managers which drivers have several violations in a short time';

    public function handle(FleetAnalyticsService $analytics): int
    {
        $days = (int) config('alerts.repeat_violator_days', 30);

        // A driver is flagged once per window: the trail shows when, so a daily run does not repeat itself.
        $fresh = $analytics->repeatViolators()->reject(fn ($row) => $this->flaggedRecently($row['driver'], $days))->values();

        if ($fresh->isEmpty()) {
            $this->info('No new repeat violators.');

            return self::SUCCESS;
        }

        foreach ($fresh as $row) {
            $this->line(($row['driver']->driver_code ?: '#'.$row['driver']->id).': '.$row['count'].' violations in '.$days.' days');
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        Notifier::toRole('driver_manager', new RepeatViolatorFlag($fresh, $days));

        foreach ($fresh as $row) {
            Audit::log(Audit::VIOLATION, 'repeat_violator_flagged',
                'Flagged driver '.($row['driver']->driver_code ?: '#'.$row['driver']->id).' as a repeat violator ('.$row['count'].' violations in '.$days.' days)',
                $row['driver'], ['violations' => $row['count'], 'days' => $days, 'fines' => $row['fines']]);
        }

        $this->info('Flagged '.$fresh->count().' driver(s).');

        return self::SUCCESS;
    }

    private function flaggedRecently(Driver $driver, int $days): bool
    {
        return Activity::where('log_name', Audit::VIOLATION)
            ->where('event', 'repeat_violator_flagged')
            ->where('subject_type', $driver->getMorphClass())
            ->where('subject_id', $driver->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->exists();
    }
}
