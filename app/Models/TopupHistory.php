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
    ];

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
        return ['user_id', 'wallet_id', 'amount_added', 'payment_method', 'status'];
    }

    protected function auditLabel(): string
    {
        return 'top-up #'.$this->getKey().' ('.number_format((float) $this->amount_added, 2).')';
    }
}
