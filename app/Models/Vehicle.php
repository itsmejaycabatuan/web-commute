<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Vehicle
 *
 * @property int $id
 * @property int|null $driver_id
 * @property int|null $year
 * @property string|null $brand
 * @property string|null $model
 * @property string|null $plate_number
 * @property string|null $status
 * @property string|null $fuel_type
 * @property string|null $tank_capacity
 * @property string|null $vin
 * @property string|null $location
 * @property \Illuminate\Support\Carbon|null $acquistion_date
 * @property \Illuminate\Support\Carbon|null $exp_disposal_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Driver|null $driver
 * @property-read mixed $driver_name
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle query()
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereAcquistionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereBrand($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereDriverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereExpDisposalDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereFuelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereModel($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle wherePlateNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereTankCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereVin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereYear($value)
 * @mixin \Eloquent
 */
class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'year',
        'brand',
        'model',
        'plate_number',
        'status',
        'fuel_type',
        'tank_capacity',
        'vin',
        'location',
        'acquistion_date',
        'exp_disposal_date',
    ];

    protected $casts = [
        'year' => 'integer',
        'acquistion_date' => 'date',
        'exp_disposal_date' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    // Used by the view as v.driver_name
    public function getDriverNameAttribute()
    {
        return $this->driver?->name;
    }
}
