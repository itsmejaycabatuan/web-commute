<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto clock-out
    |--------------------------------------------------------------------------
    |
    | `php artisan shifts:auto-close` closes a shift the driver forgot to end:
    | once it has been open for `auto_close_after_hours`, or once midnight has
    | passed (when `auto_close_at_midnight` is on).
    |
    | Nobody knows when the driver really stopped, so the shift is credited
    | `auto_close_credit_hours` (a regular day, no overtime) and flagged for the
    | Driver Manager to review.
    |
    */

    'auto_close_after_hours' => (float) env('TIMEKEEPING_AUTO_CLOSE_AFTER_HOURS', 14),

    'auto_close_at_midnight' => (bool) env('TIMEKEEPING_AUTO_CLOSE_AT_MIDNIGHT', true),

    'auto_close_credit_hours' => (float) env('TIMEKEEPING_AUTO_CLOSE_CREDIT_HOURS', 8),

    /*
    |--------------------------------------------------------------------------
    | Driver inactivity
    |--------------------------------------------------------------------------
    |
    | `php artisan drivers:inactivity-check` counts the days since a driver last
    | clocked in (leave a Driver Manager recorded also counts as activity).
    |
    | After `inactivity_warn_after_days` the driver is emailed and told to contact
    | HR. Once they have been away for MORE than `inactivity_suspend_after_days`
    | they are suspended, but only after the warning has been out for a day.
    | `hr_email` is shown in the warning so the driver knows where to write.
    |
    */

    'inactivity_warn_after_days' => (int) env('DRIVER_INACTIVITY_WARN_AFTER_DAYS', 3),

    'inactivity_suspend_after_days' => (int) env('DRIVER_INACTIVITY_SUSPEND_AFTER_DAYS', 7),

    'hr_email' => env('HR_EMAIL'),

];
