<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Driver codes are generated (DRV-000001, DRV-000002, ...), never typed in.
     * Drivers that registered before that, and so have no code yet, get one now.
     */
    public function up()
    {
        $next = DB::table('drivers')
            ->where('driver_code', 'like', 'DRV-%')
            ->pluck('driver_code')
            ->map(fn ($code) => (int) substr($code, 4))
            ->max() ?? 0;

        DB::table('drivers')
            ->whereNull('driver_code')
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id) use (&$next) {
                $next++;
                DB::table('drivers')->where('id', $id)->update([
                    'driver_code' => 'DRV-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT),
                ]);
            });
    }

    public function down()
    {
        // The generated codes are kept: they cannot be told apart from older ones.
    }
};
