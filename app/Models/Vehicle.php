<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\\Models\\Vehicle
 *
 * @property int $id
 * @property int|null $year
 * @property string|null $brand
 * @property string|null $model
 * @property string|null $plate_number
 * @property string|null $status
 * @property string|null $fuel_type
 * @property string|null $tank_capacity
 * @property string|null $vin
 * @property string|null $location
 * @property Carbon|null $acquistion_date
 * @property Carbon|null $exp_disposal_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Driver> $drivers
 * @property-read Collection<int, VehicleMaintenanceLog> $maintenanceLogs
 * @property-read Collection<int, PreventiveMaintenance> $preventiveMaintenances
 * @property-read int|null $preventive_maintenances_count
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle newModelQuery()
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle newQuery()
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle query()
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereAcquistionDate($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereBrand($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereCreatedAt($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereDriverId($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereExpDisposalDate($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereFuelType($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereId($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereLocation($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereModel($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle wherePlateNumber($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereStatus($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereTankCapacity($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereUpdatedAt($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereVin($value)
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereYear($value)
 * @property Carbon|null $acquisition_date
 * @method static \\Illuminate\\Database\\Eloquent\\Builder|Vehicle whereAcquisitionDate($value)
 * @mixin \\Eloquent
 */
class Vehicle extends Model
{
    use Audited;
    use HasFactory;

    protected $fillable = [
        'year',
        'brand',
        'model',
        'plate_number',
        'status',
        'fuel_type',
        'tank_capacity',
        'vin',
        'location',
        'acquisition_date',
        'exp_disposal_date',
        'is_active',
    ];

    protected $casts = [
        'year' => 'integer',
        'acquisition_date' => 'date',
        'exp_disposal_date' => 'date',
    ];

    /** A vehicle is shared by at most this many drivers, who take turns day by day. */
    public const MAX_DRIVERS = 2;

    /** The drivers assigned to this vehicle (one or two). */
    public function drivers()
    {
        return $this->belongsToMany(Driver::class, 'driver_vehicle')->withTimestamps()->withPivot('id');
    }

    /**
     * Take the vehicle off the live map unless a driver still has a shift open on it. Its recent position rows
     * are aged past the 5-minute live window rather than deleted: the GPS history (distance driven, analytics)
     * hangs off them. Used when a shift is closed for the driver (auto-close) or by the system (suspension).
     */
    public function leaveMapIfNotDriven(): bool
    {
        if (TimeKeeping::open()->where('vehicle_id', $this->id)->exists()) {
            return false;
        }

        VehicleLocation::where('vehicle_id', $this->id)
            ->where('last_update', '>=', now()->subMinutes(5))
            ->update(['last_update' => now()->subHour()]);
        static::where('id', $this->id)->update(['is_active' => false]);

        return true;
    }

    /** The Maintenance Manager's roster: which of the drivers drives it on which date. */
    public function dutyDays()
    {
        return $this->hasMany(VehicleDutyDay::class);
    }

    /** Names of the assigned drivers, e.g. "Ramon Dela Cruz & Liza Mercado". Null when unassigned. */
    public function getDriverNameAttribute(): ?string
    {
        $names = $this->drivers->sortBy('pivot.id')->pluck('name')->filter()->all();

        return $names ? implode(' & ', $names) : null;
    }

    /**
     * The driver who may drive this vehicle on a date. With one driver that is always them. With two it
     * is whoever the roster names for that date — and nobody if the roster has no line for it.
     */
    public function dutyDriverOn(\DateTimeInterface|string|null $date = null): ?Driver
    {
        $drivers = $this->drivers;

        if ($drivers->count() <= 1) {
            return $drivers->first();
        }

        $day = $this->dutyDays()->whereDate('duty_date', Carbon::parse($date ?? today())->toDateString())->first();

        return $day ? $drivers->firstWhere('id', $day->driver_id) : null;
    }

    // app/Models/Vehicle.php
    public function preventiveMaintenances()
    {
        return $this->hasMany(PreventiveMaintenance::class);
    }

    public function maintenanceLogs()
    {
        return $this->hasMany(VehicleMaintenanceLog::class);
    }

    // `is_active` is a heartbeat flag flipped by the scheduler and broadcasts, so it is not audited.
    protected function auditCategory(): string
    {
        return Audit::FLEET;
    }

    protected function auditAttributes(): array
    {
        return ['year', 'brand', 'model', 'plate_number', 'status', 'fuel_type', 'tank_capacity', 'vin', 'location', 'acquisition_date', 'exp_disposal_date'];
    }

    protected function auditLabel(): string
    {
        return 'vehicle '.($this->plate_number ?: '#'.$this->getKey());
    }
}
