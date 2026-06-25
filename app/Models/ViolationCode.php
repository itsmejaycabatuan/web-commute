<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\ViolationCode
 *
 * @property int $id
 * @property string $code
 * @property string $violation_name
 * @property string $first_offense
 * @property string $second_offense
 * @property string $third_offense
 * @property string|null $fourth_offense
 * @property int $is_revoked
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode query()
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereFirstOffense($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereFourthOffense($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereIsRevoked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereSecondOffense($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereThirdOffense($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ViolationCode whereViolationName($value)
 *
 * @mixin \Eloquent
 */
class ViolationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'violation_name',
        'first_offense',
        'second_offense',
        'third_offense',
        'fourth_offense',
        'is_revoked',
    ];
}
