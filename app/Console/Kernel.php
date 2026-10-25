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
        // Settles or fails gateway payments whose webhook never arrived, so none sit pending for hours.
        $schedule->command('payments:reconcile')->everyTenMinutes();
        // Closes shifts a driver forgot to end, so hours and overtime are never left blank.
        $schedule->command('shifts:auto-close')->everyFifteenMinutes();
        // A morning digest of overdue and soon-due services for the Maintenance Managers.
        $schedule->command('maintenance:due-alerts')->dailyAt('07:00');
        // Warns drivers who have not clocked in for days (to contact HR) and suspends those gone over a week.
        $schedule->command('drivers:inactivity-check')->dailyAt('08:00');
        // Keeps vehicles driven: releases drivers whose licence expired, fills vacant seats with a free driver.
        $schedule->command('vehicles:cover-vacancies')->hourly();
        // Daily checks that turn the records into flags: drivers piling up violations, scans never paid for.
        $schedule->command('drivers:flag-repeat-violators')->dailyAt('09:00');
        $schedule->command('fares:flag-evasion')->dailyAt('09:30');
        // The Admins' weekly report (Mondays, covering last week) and monthly report (the 1st, covering last month).
        $schedule->command('reports:send weekly')->weeklyOn(1, '06:00');
        $schedule->command('reports:send monthly')->monthlyOn(1, '06:30');
        // Registration-paper photos from phone scans never outlive their session.
        $schedule->command('vehicle-scans:purge')->everyTenMinutes();
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
