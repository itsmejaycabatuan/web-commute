<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Services\ReplacementDriverService;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
    use Audited;
    use HasFactory;

    // Creation is logged explicitly (Audit::log) so the entry can say how and by whom.
    protected static $recordEvents = ['updated', 'deleted'];

    /** A licence this close to its expiry date is flagged as expiring. */
    public const LICENSE_EXPIRING_DAYS = 30;

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
        'inactivity_warned_at',
        'reactivated_at',
        'license_image_data',
        'license_image_mime',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_rejected' => 'boolean',
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
        'inactivity_warned_at' => 'datetime',
        'reactivated_at' => 'datetime',
    ];

    /**
     * The driver code is generated, never typed in: DRV-000001, DRV-000002, ...
     */
    protected static function booted(): void
    {
        static::creating(function (Driver $driver) {
            if (blank($driver->driver_code)) {
                $driver->driver_code = static::nextDriverCode();
            }
        });
    }

    public static function nextDriverCode(): string
    {
        $highest = static::query()
            ->where('driver_code', 'like', 'DRV-%')
            ->pluck('driver_code')
            ->map(fn ($code) => (int) substr($code, 4))
            ->max() ?? 0;

        return 'DRV-'.str_pad((string) ($highest + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Registered, but an administrator has neither approved nor rejected the
     * account yet. A pending driver can only be approved or rejected.
     */
    public function isPending(): bool
    {
        return ! $this->is_approved && ! $this->is_rejected;
    }

    /**
     * Days left on the licence: negative once it has expired, null when no
     * expiry date is on file.
     */
    public function licenseDaysRemaining(): ?int
    {
        if (! $this->expiration_date) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays(Carbon::parse($this->expiration_date)->startOfDay(), false);
    }

    /** 'expired' | 'expiring' (within 30 days) | 'valid' | 'unknown' */
    public function licenseState(): string
    {
        $days = $this->licenseDaysRemaining();

        return match (true) {
            $days === null => 'unknown',
            $days < 0 => 'expired',
            $days <= self::LICENSE_EXPIRING_DAYS => 'expiring',
            default => 'valid',
        };
    }

    /**
     * The warning a driver sees at every sign-in while their licence is expiring
     * or expired; null when there is nothing to warn about.
     */
    public function licenseNotice(): ?string
    {
        $days = $this->licenseDaysRemaining();
        $date = $this->expiration_date ? Carbon::parse($this->expiration_date)->format('M j, Y') : null;

        return match ($this->licenseState()) {
            'expired' => "Your driver's license expired on {$date}. Contact the administrator to renew it.",
            'expiring' => $days === 0
                ? "Your driver's license expires today ({$date}). Renew it as soon as possible."
                : "Your driver's license expires in {$days} ".($days === 1 ? 'day' : 'days')." ({$date}). Renew it before it lapses.",
            default => null,
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timeKeeping()
    {
        return $this->hasMany(TimeKeeping::class);
    }

    /** The vehicle this driver is assigned to (as a collection of at most one: shared vehicles have two drivers). */
    public function vehicle()
    {
        return $this->belongsToMany(Vehicle::class, 'driver_vehicle')->withTimestamps();
    }

    /** The driver's own duty days, from the vehicle rosters. */
    public function dutyDays()
    {
        return $this->hasMany(VehicleDutyDay::class);
    }

    /** The one vehicle this driver is assigned to, if any. */
    public function assignedVehicle(): ?Vehicle
    {
        return $this->vehicle()->first();
    }

    /** The driver sharing this driver's vehicle, if there is one. */
    public function coDriver(): ?Driver
    {
        $vehicle = $this->assignedVehicle();

        return $vehicle?->drivers()->where('drivers.id', '!=', $this->id)->first();
    }

    /**
     * Why this driver may not go on shift today, or null if they may: no vehicle assigned, or the vehicle
     * is shared and today is not their duty day. Used by clock-in, the availability toggle, the map and
     * the location broadcast.
     */
    public function dutyBlock(\DateTimeInterface|string|null $date = null): ?string
    {
        $vehicle = $this->assignedVehicle();

        if (! $vehicle) {
            return 'You have no vehicle assigned yet, so you cannot clock in or set your status to active. Please contact the Maintenance Manager.';
        }

        $duty = $vehicle->dutyDriverOn($date);

        if ($duty && $duty->id === $this->id) {
            return null;
        }

        if ($duty) {
            return "Today is {$duty->name}'s day on this vehicle ({$vehicle->plate_number}). You can drive it on your own duty days.";
        }

        return "No duty roster is set for {$vehicle->plate_number} today, so you cannot clock in or set your status to active. Please contact the Maintenance Manager.";
    }

    /** The next date (today included) on which this driver is rostered, for the dashboard. */
    public function nextDutyDate(): ?\Illuminate\Support\Carbon
    {
        $date = $this->dutyDays()->whereDate('duty_date', '>=', today())->orderBy('duty_date')->value('duty_date');

        return $date ? \Illuminate\Support\Carbon::parse($date) : null;
    }

    /**
     * This driver's own view of the duty roster: the next `$days` days (today first) of the vehicle they
     * share, each marked as theirs, the co-driver's, or not scheduled. Null when there is nothing to
     * schedule — no vehicle, or a vehicle they have to themselves (they may drive it any day).
     *
     * @return array{vehicle: Vehicle, co_driver: Driver, days: array<int, array{date: Carbon, owner: ?string}>, mine: int, scheduled: int}|null
     */
    public function dutySchedule(int $days = 14): ?array
    {
        $vehicle = $this->assignedVehicle();
        $coDriver = $vehicle?->drivers()->where('drivers.id', '!=', $this->id)->first();

        if (! $vehicle || ! $coDriver) {
            return null;
        }

        $from = today();
        $roster = $vehicle->dutyDays()
            ->whereBetween('duty_date', [$from->toDateString(), $from->copy()->addDays($days - 1)->toDateString()])
            ->get()
            ->keyBy(fn ($day) => $day->duty_date->toDateString());

        $list = [];
        $mine = $scheduled = 0;

        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);
            $driverId = $roster->get($date->toDateString())?->driver_id;
            $owner = $driverId === null ? null : ($driverId === $this->id ? 'me' : 'co');

            $list[] = ['date' => $date, 'owner' => $owner];
            $scheduled += $owner === null ? 0 : 1;
            $mine += $owner === 'me' ? 1 : 0;
        }

        return ['vehicle' => $vehicle, 'co_driver' => $coDriver, 'days' => $list, 'mine' => $mine, 'scheduled' => $scheduled];
    }

    /**
     * A driver can only go on shift (clock in, or set themselves active) while a
     * vehicle is assigned to them.
     */
    public function hasAssignedVehicle(): bool
    {
        return $this->vehicle()->exists();
    }

    /**
     * True while the driver's last shift was closed by the system (they never clocked out) and they
     * have not started a new one, so their phone must stop sending the vehicle's position.
     */
    public function shiftWasAutoClosed(): bool
    {
        if (TimeKeeping::open()->where('driver_id', $this->id)->exists()) {
            return false;
        }

        return TimeKeeping::where('driver_id', $this->id)
            ->where('auto_closed_at', '>=', now()->subHours(12))
            ->exists();
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

        // A shift they had open ends now, so it stops accruing hours for someone who can no longer work.
        $shiftVehicleIds = $this->endOpenShifts();

        // Off the vehicle (and its roster), and the seat they held goes out for a replacement.
        $vehicles = $this->releaseFromVehicles();

        // A vehicle nobody else is driving leaves the live map.
        Vehicle::whereIn('id', $vehicles->pluck('id')->merge($shiftVehicleIds)->unique())->get()
            ->each(fn (Vehicle $vehicle) => $vehicle->leaveMapIfNotDriven());

        foreach ($vehicles as $vehicle) {
            try {
                app(ReplacementDriverService::class)->vacate($vehicle, $this, DriverVacancy::SUSPENDED);
            } catch (\Throwable $e) {
                // Finding a replacement must never undo or fail a suspension; the vacancy job retries.
                report($e);
            }
        }
    }

    /**
     * Clock the driver out at this moment, as a clock-out would: the hours run to now, with overtime past 8.
     * A shift that has been open longer than the auto-close limit was forgotten long ago, so its end is not
     * known; it is credited like an auto-closed shift and flagged for the Driver Manager to review.
     *
     * @return array<int, int> ids of the vehicles the closed shifts were worked on
     */
    public function endOpenShifts(): array
    {
        $vehicleIds = [];

        foreach (TimeKeeping::open()->where('driver_id', $this->id)->get() as $entry) {
            $start = $entry->startedAt();

            if (! $start) {
                continue;
            }

            $hours = round($start->diffInMinutes(now()) / 60, 2);

            if ($hours > (float) config('timekeeping.auto_close_after_hours', 14)) {
                $hours = round(min((float) config('timekeeping.auto_close_credit_hours', 8), $hours), 2);
                $entry->update([
                    'time_out' => $start->copy()->addMinutes((int) round($hours * 60))->format('h:i A'),
                    'hours_worked' => $hours,
                    'overtime_hours' => 0,
                    'auto_closed_at' => now(),
                    'reviewed_at' => null,
                ]);
            } else {
                $entry->update([
                    'time_out' => now()->timezone('Asia/Manila')->format('h:i A'),
                    'hours_worked' => $hours,
                    'overtime_hours' => $hours > 8 ? round($hours - 8, 2) : 0,
                ]);
            }

            $vehicleIds[] = (int) ($entry->vehicle_id ?? $this->assignedVehicle()?->id);
        }

        return array_values(array_filter(array_unique($vehicleIds)));
    }

    /**
     * Take the driver off every vehicle they hold, and off its roster from today on (past duty days
     * stay as history). Returns the vehicles they left.
     *
     * @return \Illuminate\Support\Collection<int, Vehicle>
     */
    public function releaseFromVehicles(): \Illuminate\Support\Collection
    {
        $vehicles = $this->vehicle()->get();

        $this->vehicle()->detach();
        $this->dutyDays()->whereDate('duty_date', '>=', today())->delete();

        return $vehicles;
    }

    public function unsuspend(): void
    {
        $this->forceFill([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspension_reason' => null,
            'inactivity_warned_at' => null,
            // A fresh inactivity clock, so the automatic check does not suspend them again at once.
            'reactivated_at' => now(),
            'status' => 'inactive',
        ])->save();
    }

    /**
     * The day the inactivity clock counts from: the latest time-keeping entry (a shift, or leave a
     * Driver Manager recorded), else when the account was created or last reinstated.
     */
    public function inactiveSince(): Carbon
    {
        $lastEntry = $this->timeKeeping()->max('date');

        return collect([$lastEntry ? Carbon::parse($lastEntry) : null, $this->created_at, $this->reactivated_at])
            ->filter()
            ->max()
            ->copy()
            ->startOfDay();
    }

    /** Whole days since the driver last clocked in (see inactiveSince()). */
    public function daysInactive(): int
    {
        return (int) $this->inactiveSince()->diffInDays(today());
    }

    // Creation and suspension are logged explicitly; availability (`status`) and the licence image columns are deliberately not audited.
    protected function auditCategory(): string
    {
        return Audit::DRIVER;
    }

    protected function auditAttributes(): array
    {
        return ['driver_code', 'name', 'expiration_date', 'contact_info', 'license_number', 'license_code', 'license_status', 'is_approved', 'is_rejected'];
    }

    protected function auditLabel(): string
    {
        return 'driver '.($this->driver_code ?: '#'.$this->getKey());
    }

    protected function auditDescription(string $event): string
    {
        if ($event === 'updated' && $this->isDirty('is_approved') && $this->is_approved) {
            return 'Approved '.$this->auditLabel();
        }
        if ($event === 'updated' && $this->isDirty('is_rejected') && $this->is_rejected) {
            return 'Rejected '.$this->auditLabel();
        }

        return ucfirst($event).' '.$this->auditLabel();
    }
}
