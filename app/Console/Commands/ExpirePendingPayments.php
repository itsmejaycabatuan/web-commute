<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\TopupHistory;
use Illuminate\Console\Command;

class ExpirePendingPayments extends Command
{
    protected $signature = 'payments:expire-pending';

    protected $description = 'Close pending payments and top-ups that were not completed within '.Payment::PENDING_TTL_HOURS.' hours';

    public function handle(): int
    {
        $count = Payment::expireStale();

        $topups = TopupHistory::expireStale();

        $this->info("Expired {$count} pending payment(s) and {$topups} pending top-up(s).");

        return self::SUCCESS;
    }
}
