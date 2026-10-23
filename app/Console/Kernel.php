<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command("auth:clear-resets")->everyFifteenMinutes();
        $schedule->command('vehicle:offline-check')->everyMinute();
        $schedule->command('payments:expire-pending')->hourly();
        // Closes shifts a driver forgot to end, so hours and overtime are never left blank.
        $schedule->command('shifts:auto-close')->everyFifteenMinutes();
        // A morning digest of overdue and soon-due services for the Maintenance Managers.
        $schedule->command('maintenance:due-alerts')->dailyAt('07:00');
        // Audit trail retention (ACTIVITY_LOG_RETENTION_DAYS, default 365) — keeps activity_log from growing forever.
        $schedule->command('activitylog:clean')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
