<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification channels
    |--------------------------------------------------------------------------
    |
    | Every message the system sends on its own goes through App\Support\Notifier and picks its
    | channels from here. Email is the only channel today; add `database` (in-app list) or a push/SMS
    | channel to this comma-separated list once one exists and every notification starts using it.
    |
    */

    'channels' => array_values(array_filter(array_map('trim', explode(',', (string) env('NOTIFICATION_CHANNELS', 'mail'))))),

    /*
    |--------------------------------------------------------------------------
    | Repeated payment-gateway failures
    |--------------------------------------------------------------------------
    |
    | When the gateway errors (a checkout could not be opened, a payment could not be looked up, a
    | payment came back declined) `gateway_failure_threshold` times inside `gateway_failure_window_minutes`,
    | the Admins are emailed once for that window.
    |
    */

    'gateway_failure_threshold' => (int) env('GATEWAY_FAILURE_THRESHOLD', 3),

    'gateway_failure_window_minutes' => (int) env('GATEWAY_FAILURE_WINDOW_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Repeat violators
    |--------------------------------------------------------------------------
    |
    | A driver with `repeat_violator_count` or more violations inside `repeat_violator_days` days is
    | flagged to the Driver Managers (once per window).
    |
    */

    'repeat_violator_count' => (int) env('REPEAT_VIOLATOR_COUNT', 3),

    'repeat_violator_days' => (int) env('REPEAT_VIOLATOR_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Fare evasion
    |--------------------------------------------------------------------------
    |
    | A scan that was never followed by a fare payment is an "abandoned" scan. A commuter with
    | `fare_evasion_scans` or more of them inside `fare_evasion_days` days is flagged to the Admins.
    |
    */

    'fare_evasion_scans' => (int) env('FARE_EVASION_SCANS', 3),

    'fare_evasion_days' => (int) env('FARE_EVASION_DAYS', 7),

];
