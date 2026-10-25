<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * One desktop-to-phone scan of a vehicle's registration papers (see the migration for the flow).
 */
class VehicleScanSession extends Model
{
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const READY = 'ready';
    public const FAILED = 'failed';
    public const CONSUMED = 'consumed';
    public const CANCELLED = 'cancelled';

    /** The private disk (storage/app): nothing here is web-accessible. */
    public const DISK = 'local';

    protected $fillable = ['token', 'user_id', 'status', 'expires_at', 'files', 'result', 'error', 'uploaded_at', 'consumed_at', 'vehicle_id'];

    protected $casts = [
        'expires_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'consumed_at' => 'datetime',
        'files' => 'array',
        'result' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /** Can the phone still send photos to this session? (Again is fine: a retake replaces the first try.) */
    public function acceptsUploads(): bool
    {
        return ! $this->isExpired() && in_array($this->status, [self::PENDING, self::FAILED, self::READY], true);
    }

    /** What the desktop is told: an expired session is "expired" whatever its stored status. */
    public function visibleStatus(): string
    {
        return $this->isExpired() && ! in_array($this->status, [self::CONSUMED, self::CANCELLED], true) ? 'expired' : $this->status;
    }

    public function directory(): string
    {
        return 'vehicle-scans/'.$this->id;
    }

    /** Remove the uploaded photos (the row stays for a day as a record that a scan was used). */
    public function deleteFiles(): void
    {
        Storage::disk(self::DISK)->deleteDirectory($this->directory());
        $this->forceFill(['files' => null])->save();
    }
}
