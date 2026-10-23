<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\VehicleDutyDay
 *
 * One line of a vehicle's duty roster: on this date, this driver drives it.
 *
 * @property int $id
 * @property int $vehicle_id
 * @property int $driver_id
 * @property \Illuminate\Support\Carbon $duty_date
 * @mixin \Eloquent
 */
class VehicleDutyDay extends Model
{
    protected $fillable = ['vehicle_id', 'driver_id', 'duty_date'];

    protected $casts = ['duty_date' => 'date'];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
