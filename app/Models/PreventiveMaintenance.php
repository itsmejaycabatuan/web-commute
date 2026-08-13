<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreventiveMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'fleet_id',
        'task_id',
        'last_service_odo',
        'last_service_date',
        'last_service_cost',
        'comments',
    ];

    protected $casts = [
        'last_service_date' => 'date',
        'last_service_cost' => 'decimal:2',
    ];

    public function fleet()
    {
        return $this->belongsTo(FleetInventory::class);
    }

    public function maintenanceTask()
    {
        return $this->belongsTo(MaintenanceTask::class, 'task_id');
    }
}
