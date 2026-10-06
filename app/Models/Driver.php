<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Driver
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $driver_code
 * @property string|null $name
 * @property string|null $expiration_date
 * @property string|null $contact_info
 * @property string|null $license_number
 * @property string|null $license_code
 * @property string|null $license_image_path
 * @property string|null $license_status
 * @property int $is_approved
 * @property int $is_rejected
 * @property string|null $status
 * @property string|null $license_image_data
 * @property string|null $license_image_mime
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TimeKeeping> $timeKeeping
 * @property-read int|null $time_keeping_count
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Vehicle> $vehicle
 * @property-read int|null $vehicle_count
 * @method static \Illuminate\Database\Eloquent\Builder|Driver newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Driver newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Driver query()
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereContactInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereDriverCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereExpirationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereIsApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereIsRejected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLicenseCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLicenseImageData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLicenseImageMime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLicenseImagePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLicenseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereLicenseStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Driver whereUserId($value)
 * @mixin \Eloquent
 */
class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'driver_code',
        'name',
        'expiration_date',
        'contact_info',
        'license_number',
        'license_code',
        'license_image_path',
        'license_status',
        'is_approved',
        'is_rejected',
        'status',
        'is_suspended',
        'suspended_at',
        'suspension_reason',
        'license_image_data',
        'license_image_mime',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_rejected' => 'boolean',
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timeKeeping()
    {
        return $this->hasMany(TimeKeeping::class);
    }

    public function vehicle()
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * UCN_SC_E012 — a suspended driver is refused at sign-in and may not clock in.
     */
    public function isSuspended(): bool
    {
        return (bool) $this->is_suspended;
    }

    /**
     * Suspending also takes the driver off shift and frees any vehicle they hold,
     * so a suspended driver never shows as active on the map or in reports.
     */
    public function suspend(?string $reason = null): void
    {
        $this->forceFill([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
            'status' => 'suspended',
        ])->save();

        $this->vehicle()->update(['driver_id' => null]);
    }

    public function unsuspend(): void
    {
        $this->forceFill([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspension_reason' => null,
            'status' => 'inactive',
        ])->save();
    }
}
