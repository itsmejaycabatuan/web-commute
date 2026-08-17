<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\TimeKeeping
 *
 * @property int $id
 * @property int $driver_id
 * @property string $date
 * @property string|null $time_in
 * @property string|null $time_out
 * @property string|null $hours_worked
 * @property string|null $overtime_hours
 * @property int|null $sick
 * @property int|null $vacation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Driver $driver
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping query()
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereDriverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereHoursWorked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereOvertimeHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereSick($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereTimeIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereTimeOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereVacation($value)
 * @mixin \Eloquent
 */
class TimeKeeping extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'date',
        'time_in',
        'time_out',
        'hours_worked',
        'overtime_hours',
        'sick',
        'vacation',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
