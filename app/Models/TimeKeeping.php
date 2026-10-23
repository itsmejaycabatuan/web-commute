<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\TimeKeeping
 *
 * @property int $id
 * @property int $driver_id
 * @property string $date
 * @property string|null $time_in
 * @property string|null $time_out
 * @property string|null $hours_worked
 * @property string|null $overtime_hours
 * @property int|null $sick
 * @property int|null $vacation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Driver $driver
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping query()
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereDriverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereHoursWorked($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereOvertimeHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereSick($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereTimeIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereTimeOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TimeKeeping whereVacation($value)
 * @mixin \Eloquent
 */
class TimeKeeping extends Model
{
    use Audited;
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'vehicle_id',
        'date',
        'time_in',
        'time_out',
        'hours_worked',
        'overtime_hours',
        'auto_closed_at',
        'reviewed_at',
        'sick',
        'vacation',
    ];

    protected $casts = [
        'auto_closed_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /** Shifts that were started and never ended. */
    public function scopeOpen($query)
    {
        return $query->whereNull('time_out')->whereNotNull('time_in')
            ->where(fn ($q) => $q->whereNull('sick')->orWhere('sick', 0))
            ->where(fn ($q) => $q->whereNull('vacation')->orWhere('vacation', 0));
    }

    /** Auto-closed shifts a Driver Manager has not yet confirmed. */
    public function scopeNeedsReview($query)
    {
        return $query->whereNotNull('auto_closed_at')->whereNull('reviewed_at');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function wasAutoClosed(): bool
    {
        return $this->auto_closed_at !== null;
    }

    /** When the shift began, from the entry's date and its free-form "06:00 AM" / "06:00" time. */
    public function startedAt(): ?Carbon
    {
        if (! $this->time_in) {
            return null;
        }

        try {
            return Carbon::parse($this->getRawOriginal('date').' '.$this->time_in, 'Asia/Manila');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    protected function auditCategory(): string
    {
        return Audit::TIMEKEEPING;
    }

    protected function auditAttributes(): array
    {
        return ['driver_id', 'date', 'time_in', 'time_out', 'hours_worked', 'overtime_hours', 'auto_closed_at', 'reviewed_at', 'sick', 'vacation'];
    }

    protected function auditDescription(string $event): string
    {
        if ($event === 'updated' && $this->isDirty('auto_closed_at') && $this->auto_closed_at) {
            return 'Auto-closed '.$this->auditLabel().' after the driver did not clock out';
        }
        if ($event === 'updated' && $this->isDirty('reviewed_at') && $this->reviewed_at) {
            return 'Reviewed auto-closed '.$this->auditLabel();
        }

        return ucfirst($event).' '.$this->auditLabel();
    }

    protected function auditLabel(): string
    {
        return 'time entry (driver #'.$this->driver_id.', '.($this->date instanceof \DateTimeInterface ? $this->date->format('Y-m-d') : $this->date).')';
    }
}
