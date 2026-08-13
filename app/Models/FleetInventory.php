<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'maintenance_cost',
        'notes',
    ];

    protected $casts = [
        'maintenance_cost' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
