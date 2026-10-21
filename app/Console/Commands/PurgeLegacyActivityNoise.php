<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

/**
 * One-off clean-up for rows written by the old logging, which recorded every page view, map poll
 * and location broadcast as "Action performed: <method>" with no subject and no details.
 * Entries from the new audit trail never use that wording, so nothing meaningful is touched.
 *
 *   php artisan activitylog:purge-noise --dry-run     # count only
 *   php artisan activitylog:purge-noise               # delete (asks first)
 */
class PurgeLegacyActivityNoise extends Command
{
    protected $signature = 'activitylog:purge-noise {--dry-run : Only count the rows} {--force : Do not ask for confirmation}';

    protected $description = 'Delete legacy "Action performed: …" rows from the activity log';

    public function handle(): int
    {
        $noise = Activity::query()->where('description', 'like', 'Action performed:%');
        $count = (clone $noise)->count();
        $total = Activity::query()->count();

        $this->info("{$count} of {$total} activity rows are legacy \"Action performed\" noise.");

        if ($count === 0 || $this->option('dry-run')) {
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete those {$count} rows? This cannot be undone.")) {
            $this->comment('Nothing deleted.');

            return self::SUCCESS;
        }

        $deleted = 0;
        // chunked so a very large table does not hold one long lock
        do {
            $batch = (clone $noise)->limit(5000)->delete();
            $deleted += $batch;
        } while ($batch > 0);

        $this->info("Deleted {$deleted} rows.");

        return self::SUCCESS;
    }
}
