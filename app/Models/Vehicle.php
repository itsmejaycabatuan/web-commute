<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
