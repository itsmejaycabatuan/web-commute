<?php

namespace Database\Seeders;

use App\Models\QrCode;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One ready-made ride QR code, so a fresh install (or a dev database) can pay a fare straight away:
 * commuters must scan a system-made code before paying, and none exists until the Admin makes one.
 * Safe to re-run: the code has a fixed token, so it is created once and switched back on if it was
 * switched off.
 */
class QrCodeSeeder extends Seeder
{
    /** 40 letters/digits, like every generated token. Print it from the Admin → QR Codes page. */
    public const TOKEN = 'SmartCommuteSeedRideCode00000000000001';

    public function run()
    {
        $code = QrCode::firstOrCreate(
            ['token' => self::TOKEN],
            ['is_active' => true, 'created_by' => User::where('email', 'admin@gmail.com')->value('id')]
        );

        if (! $code->is_active) {
            $code->update(['is_active' => true]);
        }

        $this->command?->info('Ride QR code ready (token '.self::TOKEN.').');
    }
}
