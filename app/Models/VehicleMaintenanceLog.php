<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleMaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'fleet_id',
        'maintenance_task_id',
        'service_date',
        'mileage_at_service',
        'performed_by',
        'cost',
        'invoice_number',
        'remarks',
    ];

    protected $casts = [
        'service_date' => 'date',       // <-- add this
        'cost' => 'decimal:2',          // <-- add this
        'mileage_at_service' => 'integer', // <-- add this
    ];

    public function fleet()
    {
        return $this->belongsTo(FleetInventory::class);
    }

    public function maintenanceTask()
    {
        return $this->belongsTo(MaintenanceTask::class);
    }
}
