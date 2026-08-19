<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\VehicleMaintenanceLog
 *
 * @property int $id
 * @property int $fleet_id
 * @property int $maintenance_task_id
 * @property Carbon|null $service_date
 * @property int|null $mileage_at_service
 * @property string|null $performed_by
 * @property string|null $cost
 * @property string|null $invoice_number
 * @property string|null $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FleetInventory $fleet
 * @property-read MaintenanceTask $maintenanceTask
 * @property-read Vehicle|null $vehicle
 *
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog query()
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereFleetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereInvoiceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereMaintenanceTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereMileageAtService($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog wherePerformedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereServiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|VehicleMaintenanceLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class VehicleMaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'cost' => 'decimal:2',
        'mileage_at_service' => 'integer',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function maintenanceTask()
    {
        return $this->belongsTo(MaintenanceTask::class);
    }
}
