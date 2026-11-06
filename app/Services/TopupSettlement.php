<?php

namespace App\Services;

use App\Models\TopupHistory;
use App\Models\Wallet;
use App\Notifications\TopupFailed;
use App\Notifications\TopupReceipt;
use App\Support\Notifier;
use Illuminate\Support\Facades\DB;

/**
 * The single place a gateway top-up moves to completed or failed, whichever way the news arrived: the PayMongo
 * webhook, the commuter's return URL, or the scheduled reconciliation. Whoever gets there first wins and the
 * others find it done, so the wallet is credited — and the receipt emailed — exactly once.
 */
class TopupSettlement
{
    /**
     * Credit the wallet and mark the top-up completed (from pending, or from a closed state the gateway has since paid). Returns true only for the call that made the change.
     */
    public function settle(TopupHistory $topup): bool
    {
        $newBalance = null;

        DB::transaction(function () use ($topup, &$newBalance) {
            $fresh = TopupHistory::lockForUpdate()->find($topup->id);

            // Only the gateway's word reaches here (a verified webhook, or a lookup at PayMongo), so if it says
            // the money arrived the wallet is credited — even when the row was already closed as cancelled or
            // failed (the commuter backed out, then paid the still-open session). The one thing that must never
            // happen is a second credit, so an already completed top-up is left alone.
            if (! $fresh || $fresh->status === TopupHistory::STATUS_COMPLETED) {
                return;
            }

            $wallet = Wallet::lockForUpdate()->find($fresh->wallet_id);

            if (! $wallet) {
                return;
            }

            $newBalance = round((float) $wallet->balance + (float) $fresh->amount_added, 2);
            $wallet->update(['balance' => $newBalance]);

            $fresh->update([
                'status' => TopupHistory::STATUS_COMPLETED,
                'paid_at' => $fresh->paid_at ?? now(),
                'failed_at' => null,
                'failure_message' => null,
            ]);
        });

        $topup->refresh();

        if ($newBalance !== null) {
            Notifier::send($topup->user, new TopupReceipt($topup, $newBalance));

            return true;
        }

        return false;
    }

    /**
     * Mark a still-pending top-up failed and tell the commuter. A top-up that is already completed, failed or
     * cancelled is left alone. The wallet is never touched.
     */
    public function fail(TopupHistory $topup, string $message): bool
    {
        return $this->close($topup, TopupHistory::STATUS_FAILED, $message, true);
    }

    /** The commuter backed out of checkout, or the session expired: close it quietly. */
    public function cancel(TopupHistory $topup, string $message): bool
    {
        return $this->close($topup, TopupHistory::STATUS_CANCELLED, $message, false);
    }

    private function close(TopupHistory $topup, string $status, string $message, bool $notify): bool
    {
        $changed = false;

        DB::transaction(function () use ($topup, $status, $message, &$changed) {
            $fresh = TopupHistory::lockForUpdate()->find($topup->id);

            if (! $fresh || $fresh->status !== TopupHistory::STATUS_PENDING) {
                return;
            }

            $fresh->update([
                'status' => $status,
                'failed_at' => now(),
                'failure_message' => $message,
            ]);

            $changed = true;
        });

        $topup->refresh();

        if ($changed && $notify) {
            Notifier::send($topup->user, new TopupFailed($topup));
        }

        return $changed;
    }
}
