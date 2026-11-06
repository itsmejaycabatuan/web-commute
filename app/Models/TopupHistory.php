<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TopupHistory
 *
 * @property int $id
 * @property int $user_id
 * @property int $wallet_id
 * @property string $amount_added
 * @property string $payment_method
 * @property string $status
 * @property string|null $transaction_id
 * @property string|null $paymongo_payment_intent_id
 * @property string|null $paymongo_checkout_session_id
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property \Illuminate\Support\Carbon|null $failed_at
 * @property string|null $failure_message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Wallet $wallet
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory query()
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereAmountAdded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TopupHistory whereWalletId($value)
 * @mixin \Eloquent
 */
class TopupHistory extends Model
{
    use Audited;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'amount_added',
        'payment_method',
        'status',
        'transaction_id',
        'paymongo_payment_intent_id',
        'paymongo_checkout_session_id',
        'paid_at',
        'failed_at',
        'failure_message',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /** A gateway top-up still pending after this many hours is closed as expired (nothing was charged). */
    public const PENDING_TTL_HOURS = 12;

    /** Top-up lifecycle states. Only a completed top-up adds money to a wallet. */
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_PENDING,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Close every top-up that has been pending for longer than the limit: the gateway's checkout session is
     * long dead, so it must not sit in the ledger as "pending" forever. They become `cancelled`; the wallet
     * was never credited.
     */
    public static function expireStale(): int
    {
        $stale = static::where('status', self::STATUS_PENDING)
            ->where('created_at', '<', now()->subHours(self::PENDING_TTL_HOURS))
            ->get();

        // One by one (not a bulk update) so every closure reaches the audit trail.
        foreach ($stale as $topup) {
            $topup->update([
                'status' => self::STATUS_CANCELLED,
                'failed_at' => now(),
                'failure_message' => 'Expired: not completed within '.self::PENDING_TTL_HOURS.' hours.',
            ]);
        }

        return $stale->count();
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function wallet() {
        return $this->belongsTo(Wallet::class);
    }
    protected function auditCategory(): string
    {
        return Audit::PAYMENT;
    }

    protected function auditAttributes(): array
    {
        return ['user_id', 'wallet_id', 'transaction_id', 'amount_added', 'payment_method', 'status', 'paid_at', 'failed_at', 'failure_message'];
    }

    protected function auditLabel(): string
    {
        return 'top-up #'.$this->getKey().' ('.number_format((float) $this->amount_added, 2).')';
    }
}
