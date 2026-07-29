<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'driver_code',
        'name',
        'expiration_date',
        'contact_info',
        'license_number',
        'license_code',
        'license_image_path',
        'license_status',
        'is_approved',
        'is_rejected',
        'license_image_data',
        'license_image_mime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timeKeeping()
    {
        return $this->hasMany(TimeKeeping::class);
    }

    public function vehicle()
    {
        return $this->hasMany(Vehicle::class);
    }
}
