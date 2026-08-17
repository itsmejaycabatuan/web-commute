<?php

namespace App\Models;

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
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vc_id',
        'violation_instance',
        'violation_fine',
        'additional_penalties',
        'date_of_violation',
        'time_of_violation',
        'place_of_violation',
        'remarks',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function violationCode()
    {
        return $this->belongsTo(ViolationCode::class, 'vc_id');
    }
}
