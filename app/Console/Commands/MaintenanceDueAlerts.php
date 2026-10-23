<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\MaintenanceDueDigest;
use App\Services\MaintenanceDueService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MaintenanceDueAlerts extends Command
{
    protected $signature = 'maintenance:due-alerts {--dry-run : List what is due without emailing anyone}';

    protected $description = 'Email the Maintenance Managers the services that are overdue or due within the alert window';

    public function handle(MaintenanceDueService $due): int
    {
        $fleet = $due->fleet();

        if ($fleet->isEmpty()) {
            $this->info('Nothing is overdue or due soon.');

            return self::SUCCESS;
        }

        foreach ($fleet as $row) {
            $this->line($row['vehicle']->plate_number.': '.$row['overdue']->count().' overdue, '.$row['due_soon']->count().' due soon');
        }

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        $managers = User::role('maintenance_manager')->where('is_suspended', false)->get();

        foreach ($managers as $manager) {
            try {
                $manager->notify(new MaintenanceDueDigest($fleet));
            } catch (\Throwable $e) {
                // One bad mailbox must not stop the others from being told.
                Log::error('Maintenance due alert could not be sent.', ['user_id' => $manager->id, 'exception' => $e->getMessage()]);
            }
        }

        $this->info('Alerted '.$managers->count().' maintenance manager(s) about '.$fleet->count().' vehicle(s).');

        return self::SUCCESS;
    }
}
