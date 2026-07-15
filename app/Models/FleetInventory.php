<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'fleet_id',
        'make',
        'model',
        'year',
        'engine',
        'purchase_date',
        'purchase_cost',
        'condition',
        'maintenance_cost',
        'notes',
    ];
}
