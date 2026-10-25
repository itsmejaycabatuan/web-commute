<?php

namespace App\Services;

use App\Models\Payment;
use App\Notifications\PaymentFailed;
use App\Notifications\PaymentReceipt;
use App\Support\Notifier;
use Illuminate\Support\Facades\DB;

/**
 * The single place a fare payment moves to paid or failed, whichever way the news arrived: the PayMongo
 * webhook, the commuter's return URL, or the scheduled reconciliation. Whoever gets there first wins, the
 * others find it done, so the receipt is emailed exactly once.
 */
class PaymentSettlement
{
    /**
     * Mark the payment paid. Returns true only for the call that made the change.
     */
    public function settle(Payment $payment): bool
    {
        $changed = false;

        DB::transaction(function () use ($payment, &$changed) {
            $fresh = Payment::lockForUpdate()->find($payment->id);

            if (! $fresh || $fresh->status === Payment::STATUS_PAID) {
                return;
            }

            $fresh->update([
                'status' => Payment::STATUS_PAID,
                'paid_at' => $fresh->paid_at ?? now(),
            ]);

            $changed = true;
        });

        $payment->refresh();

        if ($changed) {
            $this->sendReceipt($payment);
        }

        return $changed;
    }

    /**
     * Mark a still-pending payment failed and tell the commuter. A payment that is already paid,
     * failed or cancelled is left alone.
     */
    public function fail(Payment $payment, string $message): bool
    {
        $changed = false;

        DB::transaction(function () use ($payment, $message, &$changed) {
            $fresh = Payment::lockForUpdate()->find($payment->id);

            if (! $fresh || $fresh->status !== Payment::STATUS_PENDING) {
                return;
            }

            $fresh->update([
                'status' => Payment::STATUS_FAILED,
                'failed_at' => now(),
                'failure_message' => $message,
            ]);

            $changed = true;
        });

        $payment->refresh();

        if ($changed) {
            Notifier::send($payment->user, new PaymentFailed($payment));
        }

        return $changed;
    }

    public function sendReceipt(Payment $payment): void
    {
        Notifier::send($payment->user, new PaymentReceipt($payment));
    }
}
