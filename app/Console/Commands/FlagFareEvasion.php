<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\FareEvasionFlag;
use App\Services\FleetAnalyticsService;
use App\Support\Audit;
use App\Support\Notifier;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

class FlagFareEvasion extends Command
{
    protected $signature = 'fares:flag-evasion {--dry-run : List the commuters without emailing or logging}';

    protected $description = 'Tell the Admins which commuters keep scanning the ride QR code without paying';

    public function handle(FleetAnalyticsService $analytics): int
    {
        $days = (int) config('alerts.fare_evasion_days', 7);

        $fresh = $analytics->abandonedScans()->reject(fn ($row) => $this->flaggedRecently($row['user_id'], $days))->values();

        if ($fresh->isEmpty()) {
            $this->info('No new fare-evasion flags.');

            return self::SUCCESS;
        }

        foreach ($fresh as $row) {
            $this->line($row['email'].': '.$row['count'].' unpaid scans in '.$days.' days');
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        Notifier::toRole('admin', new FareEvasionFlag($fresh, $days));

        foreach ($fresh as $row) {
            Audit::log(Audit::FARE, 'fare_evasion_flagged',
                'Flagged '.$row['email'].' for '.$row['count'].' scans without a fare in '.$days.' days',
                User::find($row['user_id']), ['unpaid_scans' => $row['count'], 'days' => $days]);
        }

        $this->info('Flagged '.$fresh->count().' commuter(s).');

        return self::SUCCESS;
    }

    private function flaggedRecently(int $userId, int $days): bool
    {
        return Activity::where('log_name', Audit::FARE)
            ->where('event', 'fare_evasion_flagged')
            ->where('subject_type', (new User)->getMorphClass())
            ->where('subject_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->exists();
    }
}
