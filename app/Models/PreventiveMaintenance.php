<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\PreventiveMaintenance
 *
 * @property int $id
 * @property int $fleet_id
 * @property int $task_id
 * @property int|null $last_service_odo
 * @property Carbon|null $last_service_date
 * @property string|null $last_service_cost
 * @property string|null $comments
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FleetInventory $fleet
 * @property-read MaintenanceTask $maintenanceTask
 *
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance query()
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereFleetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereLastServiceCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereLastServiceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereLastServiceOdo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PreventiveMaintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
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

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function maintenanceTask()
    {
        return $this->belongsTo(MaintenanceTask::class, 'task_id');
    }

    public function vehicleMaintenanceLog()
    {
        return $this->hasMany(VehicleMaintenanceLog::class);
    }
}
