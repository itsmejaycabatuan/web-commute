<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\TopupHistory;
use App\Services\GatewayMonitor;
use App\Services\PaymentSettlement;
use App\Services\TopupSettlement;
use App\Services\PayMongoService;
use Illuminate\Console\Command;
use RuntimeException;

class ReconcilePayments extends Command
{
    protected $signature = 'payments:reconcile {--dry-run : Ask the gateway and report, but change nothing}';

    protected $description = 'Ask PayMongo about gateway payments and wallet top-ups still pending and settle or fail them (a missed webhook)';

    /** Stop a run after this many gateway errors in a row: the gateway is down, asking more only adds noise. */
    private const MAX_CONSECUTIVE_ERRORS = 3;

    public function handle(PayMongoService $paymongo, PaymentSettlement $settlement, TopupSettlement $topupSettlement, GatewayMonitor $monitor): int
    {
        if (! $paymongo->enabled()) {
            $this->info('The payment gateway is not enabled; nothing to reconcile.');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');

        $after = now()->subMinutes((int) config('paymongo.reconcile_after_minutes', 5));
        $batch = (int) config('paymongo.reconcile_batch', 100);

        $payments = Payment::where('status', Payment::STATUS_PENDING)
            ->whereNotNull('paymongo_payment_intent_id')
            ->where('created_at', '<=', $after)
            ->orderBy('id')
            ->limit($batch)
            ->get();

        $topups = TopupHistory::where('status', TopupHistory::STATUS_PENDING)
            ->whereNotNull('paymongo_payment_intent_id')
            ->where('created_at', '<=', $after)
            ->orderBy('id')
            ->limit($batch)
            ->get();

        // Fares and wallet top-ups are asked about the same way; only what "settle" and "fail" do differs.
        $pending = collect();
        foreach ($payments as $payment) {
            $pending->push([
                'record' => $payment,
                'label' => $payment->transaction_id,
                'settle' => fn () => $settlement->settle($payment),
                'fail' => fn (string $reason) => $settlement->fail($payment, $reason),
            ]);
        }
        foreach ($topups as $topup) {
            $pending->push([
                'record' => $topup,
                'label' => $topup->transaction_id,
                'settle' => fn () => $topupSettlement->settle($topup),
                'fail' => fn (string $reason) => $topupSettlement->fail($topup, $reason),
            ]);
        }

        $settled = $failed = $still = $errors = 0;
        $consecutive = 0;

        foreach ($pending as $item) {
            $record = $item['record'];

            try {
                $intent = $paymongo->retrievePaymentIntent($record->paymongo_payment_intent_id);
                $consecutive = 0;
            } catch (RuntimeException $e) {
                $errors++;
                $consecutive++;
                $monitor->recordFailure('Reconciliation could not look up payment '.$item['label'].': '.$e->getMessage());

                if ($consecutive >= self::MAX_CONSECUTIVE_ERRORS) {
                    $this->warn('The gateway keeps failing; stopping this run.');
                    break;
                }

                continue;
            }

            if ($paymongo->isSettled($intent['status'] ?? null)) {
                $this->line("{$item['label']}: paid at the gateway — settle");
                $settled++;
                if (! $dry) {
                    ($item['settle'])();
                }
            } elseif (($reason = $paymongo->failureReason($intent)) !== null) {
                $this->line("{$item['label']}: failed at the gateway — close");
                $failed++;
                if (! $dry) {
                    ($item['fail'])($reason);
                }
            } else {
                $still++;
            }
        }

        $this->info("Checked {$pending->count()} pending payment(s) and top-up(s): {$settled} settled, {$failed} failed, {$still} still pending, {$errors} gateway error(s).");

        return self::SUCCESS;
    }
}
