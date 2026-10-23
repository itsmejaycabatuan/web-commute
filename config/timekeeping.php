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

];
