<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
 * @property Carbon|null $acquistion_date
 * @property Carbon|null $exp_disposal_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Driver|null $driver
 * @property-read mixed $driver_name
 * @property-read Collection<int, VehicleMaintenanceLog> $maintenanceLogs
 * @property-read int|null $maintenance_logs_count
 * @property-read Collection<int, PreventiveMaintenance> $preventiveMaintenances
 * @property-read int|null $preventive_maintenances_count
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
 * @property Carbon|null $acquisition_date
 * @method static \Illuminate\Database\Eloquent\Builder|Vehicle whereAcquisitionDate($value)
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
        'acquisition_date',
        'exp_disposal_date',
    ];

    protected $casts = [
        'year' => 'integer',
        'acquisition_date' => 'date',
        'exp_disposal_date' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function getDriverNameAttribute()
    {
        return $this->driver?->name;
    }

    // app/Models/Vehicle.php
    public function preventiveMaintenances()
    {
        return $this->hasMany(PreventiveMaintenance::class);
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(VehicleMaintenanceLog::class);
    }
}
