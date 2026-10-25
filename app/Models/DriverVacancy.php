<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A seat on a vehicle that lost its driver and is waiting for a replacement
 * ({@see \App\Services\ReplacementDriverService}).
 */
class DriverVacancy extends Model
{
    public const SUSPENDED = 'suspended';
    public const LICENCE_EXPIRED = 'licence_expired';

    public const FILLED = 'filled';
    public const NOT_NEEDED = 'not_needed';

    protected $fillable = [
        'vehicle_id', 'former_driver_id', 'reason', 'target_drivers', 'opened_at', 'resolved_at', 'resolution',
        'replacement_driver_id', 'last_notified_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'resolved_at' => 'datetime',
        'last_notified_at' => 'datetime',
    ];

    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function formerDriver()
    {
        return $this->belongsTo(Driver::class, 'former_driver_id');
    }

    public function replacementDriver()
    {
        return $this->belongsTo(Driver::class, 'replacement_driver_id');
    }

    public function reasonLabel(): string
    {
        return $this->reason === self::LICENCE_EXPIRED ? 'licence expired' : 'suspended';
    }
}
