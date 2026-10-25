<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\GatewayMonitor;
use App\Services\PaymentSettlement;
use App\Services\PayMongoService;
use Illuminate\Console\Command;
use RuntimeException;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile {--dry-run : Ask the gateway and report, but change nothing}';

    protected $description = 'Ask PayMongo about gateway payments still pending and settle or fail them (a missed webhook)';

    /** Stop a run after this many gateway errors in a row: the gateway is down, asking more only adds noise. */
    private const MAX_CONSECUTIVE_ERRORS = 3;

    public function handle(PayMongoService $paymongo, PaymentSettlement $settlement, GatewayMonitor $monitor): int
    {
        if (! $paymongo->enabled()) {
            $this->info('The payment gateway is not enabled; nothing to reconcile.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');

        $pending = Payment::where('status', Payment::STATUS_PENDING)
            ->whereNotNull('paymongo_payment_intent_id')
            ->where('created_at', '<=', now()->subMinutes((int) config('paymongo.reconcile_after_minutes', 5)))
            ->orderBy('id')
            ->limit((int) config('paymongo.reconcile_batch', 100))
            ->get();

        $settled = $failed = $still = $errors = 0;
        $consecutive = 0;

        foreach ($pending as $payment) {
            try {
                $intent = $paymongo->retrievePaymentIntent($payment->paymongo_payment_intent_id);
                $consecutive = 0;
            } catch (RuntimeException $e) {
                $errors++;
                $consecutive++;
                $monitor->recordFailure('Reconciliation could not look up payment '.$payment->transaction_id.': '.$e->getMessage());

                if ($consecutive >= self::MAX_CONSECUTIVE_ERRORS) {
                    $this->warn('The gateway keeps failing; stopping this run.');
                    break;
                }

                continue;
            }

            if ($paymongo->isSettled($intent['status'] ?? null)) {
                $this->line("{$payment->transaction_id}: paid at the gateway — settle");
                $settled++;
                if (! $dry) {
                    $settlement->settle($payment);
                }
            } elseif ($paymongo->isFailed($intent['status'] ?? null)) {
                $this->line("{$payment->transaction_id}: failed at the gateway — close");
                $failed++;
                if (! $dry) {
                    $settlement->fail($payment, 'The payment was not completed at the gateway.');
                }
            } else {
                $still++;
            }
        }

        $this->info("Checked {$pending->count()} pending payment(s): {$settled} settled, {$failed} failed, {$still} still pending, {$errors} gateway error(s).");

        return self::SUCCESS;
    }
}
