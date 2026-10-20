<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Payment
 *
 * @property int $id
 * @property int $paid_by
 * @property string $transaction_id
 * @property string $starting_point
 * @property string $destination
 * @property string $total_distance
 * @property int $is_discounted
 * @property string $payment_method
 * @property string $price
 * @property string $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereIsDiscounted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaidBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereStartingPoint($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereTotalDistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'paid_by',
        'starting_point',
        'destination',
        'total_distance',
        'is_discounted',
        'payment_method',
        'price',
        'transaction_id',
        'status',
        'paymongo_payment_intent_id',
        'paymongo_checkout_session_id',
        'paymongo_reference_id',
        'paid_at',
        'failed_at',
        'failure_message',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'is_discounted' => 'boolean',
    ];

    /** Payment lifecycle states. */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PAID,
        self::STATUS_FAILED,
        self::STATUS_CANCELLED,
    ];

    /** A gateway payment still pending after this many hours is closed as expired. */
    public const PENDING_TTL_HOURS = 12;

    /**
     * Close every payment that has been pending for longer than the limit: the
     * gateway's checkout session is long dead, so it must not sit in the ledger as
     * "pending" forever. They become `cancelled` (nothing was charged). Called from
     * the hourly schedule and, so it also works without a cron job, whenever a
     * payment list or checkout page is opened.
     */
    public static function expireStale(): int
    {
        return static::where('status', self::STATUS_PENDING)
            ->where('created_at', '<', now()->subHours(self::PENDING_TTL_HOURS))
            ->update([
                'status' => self::STATUS_CANCELLED,
                'failed_at' => now(),
                'failure_message' => 'Expired: not completed within '.self::PENDING_TTL_HOURS.' hours.',
            ]);
    }

    public function isSettled(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Payments that actually collected money. Revenue figures must use this:
     * pending, failed and cancelled gateway payments carry a price but no cash.
     */
    public function scopeSettled($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
