<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Support\Audit;
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
 * @property int $vehicle_id
 * @property-read \App\Models\Vehicle $vehicle
 * @method static \Illuminate\Database\Eloquent\Builder|PreventiveMaintenance whereVehicleId($value)
 * @mixin \Eloquent
 */
class PreventiveMaintenance extends Model
{
    use Audited;
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

    public const OVERDUE = 'overdue';
    public const DUE_SOON = 'due_soon';
    public const OK = 'ok';

    /** The date the next service falls due; null when the task has no time interval or was never dated. */
    public function nextDueDate(): ?Carbon
    {
        $months = $this->maintenanceTask?->months_between_service;

        if (! $this->last_service_date || ! $months) {
            return null;
        }

        return $this->last_service_date->copy()->addMonths((int) $months);
    }

    /** The odometer reading the next service falls due at; null when the task has no distance interval. */
    public function nextDueOdo(): ?int
    {
        $km = $this->maintenanceTask?->miles_between_service;

        if ($this->last_service_odo === null || ! $km) {
            return null;
        }

        return (int) $this->last_service_odo + (int) $km;
    }

    /**
     * 'overdue', 'due_soon' or 'ok'. $currentOdo is the vehicle's latest recorded odometer reading;
     * without it only the date can make a service due.
     */
    public function dueStatus(?int $currentOdo = null): string
    {
        $date = $this->nextDueDate();
        $odo = $this->nextDueOdo();
        $soonDays = (int) config('maintenance.due_soon_days', 7);
        $soonKm = (int) config('maintenance.due_soon_km', 500);

        if (($date && $date->lt(today())) || ($odo !== null && $currentOdo !== null && $currentOdo >= $odo)) {
            return self::OVERDUE;
        }

        if (($date && $date->lte(today()->addDays($soonDays)))
            || ($odo !== null && $currentOdo !== null && $odo - $currentOdo <= $soonKm)) {
            return self::DUE_SOON;
        }

        return self::OK;
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(VehicleMaintenanceLog::class, 'maintenance_id');
    }

    protected function auditCategory(): string
    {
        return Audit::FLEET;
    }

    protected function auditAttributes(): array
    {
        return ['vehicle_id', 'task_id', 'last_service_odo', 'last_service_date', 'last_service_cost', 'comments'];
    }

    protected function auditLabel(): string
    {
        return 'service record (vehicle #'.$this->vehicle_id.', task #'.$this->task_id.')';
    }
}
