<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\VehicleMaintenanceLog
 *
 * One service performed on a vehicle. `preventive_maintenances` only remembers the
 * latest service per task; this is the full, append-only history.
 *
 * @property int $id
 * @property int|null $maintenance_id
 * @property int $vehicle_id
 * @property int $maintenance_task_id
 * @property Carbon $service_date
 * @property int $mileage_at_service
 * @property string|null $performed_by
 * @property string $cost
 * @property string|null $invoice_number
 * @property string|null $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Vehicle $vehicle
 * @property-read MaintenanceTask $maintenanceTask
 * @property-read PreventiveMaintenance|null $preventiveMaintenance
 * @mixin \Eloquent
 */
class VehicleMaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_id',
        'vehicle_id',
        'maintenance_task_id',
        'service_date',
        'mileage_at_service',
        'performed_by',
        'cost',
        'invoice_number',
        'remarks',
    ];

    protected $casts = [
        'service_date' => 'date',
        'mileage_at_service' => 'integer',
        'cost' => 'decimal:2',
    ];

    /** The schedule entry this was logged from; null once that entry is gone. */
    public function preventiveMaintenance()
    {
        return $this->belongsTo(PreventiveMaintenance::class, 'maintenance_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function maintenanceTask()
    {
        return $this->belongsTo(MaintenanceTask::class, 'maintenance_task_id');
    }
}
