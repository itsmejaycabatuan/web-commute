<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scheduled reports
    |--------------------------------------------------------------------------
    |
    | `php artisan reports:send weekly` (Mondays, covering the week before) and `reports:send monthly`
    | (the 1st, covering the month before) email the Admins a summary of revenue, top-ups, violations,
    | time-keeping and maintenance cost, with the figures as a CSV.
    |
    | With `attach_activity_log` on, the audit trail for the same period is attached as a second CSV
    | (at most `activity_log_limit` rows).
    |
    */

    'attach_activity_log' => (bool) env('REPORT_ATTACH_ACTIVITY_LOG', true),

    'activity_log_limit' => (int) env('REPORT_ACTIVITY_LOG_LIMIT', 10000),

];
