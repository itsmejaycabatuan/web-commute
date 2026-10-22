<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\ViolationLog
 *
 * @property int $id
 * @property int $user_id
 * @property int $vc_id
 * @property string $violation_instance
 * @property string $violation_fine
 * @property string $payment_status
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $additional_penalties
 * @property string $date_of_violation
 * @property string $time_of_violation
 * @property string $place_of_violation
 * @property string $remarks
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\ViolationCode $violationCode
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereAdditionalPenalties($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereDateOfViolation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog wherePlaceOfViolation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereTimeOfViolation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereVcId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereViolationFine($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationLog whereViolationInstance($value)
 * @mixin \Eloquent
 */
class ViolationLog extends Model
{
    use Audited;
    use HasFactory;

    public const UNPAID = 'unpaid';
    public const PAID = 'paid';

    protected $fillable = [
        'user_id',
        'vc_id',
        'violation_instance',
        'violation_fine',
        'payment_status',
        'paid_at',
        'additional_penalties',
        'date_of_violation',
        'time_of_violation',
        'place_of_violation',
        'remarks',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAID;
    }

    /**
     * What the driver still owes for this violation: the fine until it is
     * marked paid, nothing afterwards.
     */
    public function outstandingFine(): float
    {
        return $this->isPaid() ? 0.0 : (float) $this->violation_fine;
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', self::UNPAID);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function violationCode()
    {
        return $this->belongsTo(ViolationCode::class, 'vc_id');
    }
    protected function auditCategory(): string
    {
        return Audit::VIOLATION;
    }

    protected function auditAttributes(): array
    {
        return ['user_id', 'vc_id', 'violation_instance', 'violation_fine', 'payment_status', 'additional_penalties', 'date_of_violation', 'time_of_violation', 'place_of_violation', 'remarks'];
    }

    protected function auditLabel(): string
    {
        return 'violation #'.$this->getKey();
    }

    protected function auditDescription(string $event): string
    {
        if ($event === 'updated' && $this->isDirty('payment_status')) {
            return ($this->isPaid() ? 'Marked ' : 'Reopened ').$this->auditLabel().($this->isPaid() ? ' as paid' : ' as unpaid');
        }

        return ucfirst($event).' '.$this->auditLabel();
    }
}
