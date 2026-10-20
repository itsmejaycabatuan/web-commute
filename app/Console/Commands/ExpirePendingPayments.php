<?php

namespace App\Console\Commands;

use App\Models\Payment;
use Illuminate\Console\Command;

class ExpirePendingPayments extends Command
{
    protected $signature = 'payments:expire-pending';

    protected $description = 'Close pending payments that were not completed within '.Payment::PENDING_TTL_HOURS.' hours';

    public function handle(): int
    {
        $count = Payment::expireStale();

        $this->info("Expired {$count} pending payment(s).");

        return self::SUCCESS;
    }
}
