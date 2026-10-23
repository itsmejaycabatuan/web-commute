<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\QrScan
 *
 * A commuter scanned a ride QR code. The scan stays valid for config('qr.valid_minutes') and is spent
 * by the fare payment it was made for.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $qr_code_id
 * @property \Illuminate\Support\Carbon $scanned_at
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property int|null $payment_id
 * @property-read QrCode|null $qrCode
 * @mixin \Eloquent
 */
class QrScan extends Model
{
    protected $fillable = ['user_id', 'qr_code_id', 'scanned_at', 'used_at', 'payment_id'];

    protected $casts = ['scanned_at' => 'datetime', 'used_at' => 'datetime'];

    public function qrCode()
    {
        return $this->belongsTo(QrCode::class);
    }

    /** The user's unused scan that is still within its validity window, if any. */
    public static function currentFor(int $userId): ?self
    {
        return static::with('qrCode')
            ->where('user_id', $userId)
            ->whereNull('used_at')
            ->where('scanned_at', '>=', now()->subMinutes(config('qr.valid_minutes')))
            ->latest('scanned_at')
            ->first();
    }

    /** Record a scan of an active code. */
    public static function record(int $userId, QrCode $code): self
    {
        return static::create(['user_id' => $userId, 'qr_code_id' => $code->id, 'scanned_at' => now()]);
    }
}
