<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Preventive maintenance alerts
    |--------------------------------------------------------------------------
    |
    | A service is "due soon" when it falls due within `due_soon_days` days or
    | `due_soon_km` kilometres, and "overdue" once its date has passed or the
    | vehicle's latest recorded odometer reading has reached its next-due
    | reading. `php artisan maintenance:due-alerts` emails the Maintenance
    | Managers a daily digest of both.
    |
    | With `block_active_when_overdue` on, a vehicle that has an overdue service
    | cannot be set back to Active until the service is logged.
    |
    */

    'due_soon_days' => (int) env('MAINTENANCE_DUE_SOON_DAYS', 7),

    'due_soon_km' => (int) env('MAINTENANCE_DUE_SOON_KM', 500),

    'block_active_when_overdue' => (bool) env('MAINTENANCE_BLOCK_ACTIVE_WHEN_OVERDUE', false),

];
