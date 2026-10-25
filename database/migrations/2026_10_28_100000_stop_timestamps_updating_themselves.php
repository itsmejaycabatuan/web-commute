<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * MySQL and MariaDB quietly give the first NOT NULL `timestamp` column of a table `ON UPDATE CURRENT_TIMESTAMP`
     * when it has no default. Every unrelated update of the row then reset that column to "now":
     *
     *  - qr_scans.scanned_at: a scan stayed valid past its 15 minutes whenever the row was touched;
     *  - driver_vacancies.opened_at: a vacancy looked newly opened each time it was retried;
     *  - vehicle_scan_sessions.expires_at: a scan session's expiry reset on every status change.
     *
     * These columns are set by the application and must only change when it says so.
     */
    private const COLUMNS = [
        'qr_scans' => 'scanned_at',
        'driver_vacancies' => 'opened_at',
        'vehicle_scan_sessions' => 'expires_at',
    ];

    public function up()
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::COLUMNS as $table => $column) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }
    }

    public function down()
    {
        // Putting the automatic update back would bring the bug back.
    }
};
