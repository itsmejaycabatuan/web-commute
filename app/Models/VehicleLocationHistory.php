<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleLocationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_location_id',
        'longitude',
        'latitude',
        'created_at',
        'user_id',
        'distance_from_last_pos'
    ];

    public function location() {
        return $this->belongsTo(VehicleLocation::class);
    }
}
