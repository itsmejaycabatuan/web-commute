<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Replacement drivers
    |--------------------------------------------------------------------------
    |
    | When a driver is suspended, or their licence expires, they leave their vehicle and the seat they held
    | becomes a vacancy. With `auto_replace` on, the system assigns the best free driver by itself (an
    | approved driver, not suspended, with an unexpired licence, who holds no vehicle), rebuilds the duty
    | roster if the vehicle is shared, and tells the replacement and the Maintenance Managers. If nobody is
    | free the vacancy stays open, is retried every hour and is reported to the Maintenance Managers once a day.
    |
    | `release_expired_licences` takes a driver whose licence has expired off their vehicle (the hourly
    | `vehicles:cover-vacancies` job does it), exactly as a suspension would; they keep their account.
    |
    */

    'auto_replace' => (bool) env('AUTO_REPLACE_DRIVERS', true),

    'release_expired_licences' => (bool) env('RELEASE_EXPIRED_LICENCE_DRIVERS', true),

    /** Days of alternating duty roster written when a replacement turns a vehicle into a shared one. */
    'roster_days' => (int) env('REPLACEMENT_ROSTER_DAYS', 30),

    /** How far back violations and overtime are weighed when ranking the candidates. */
    'ranking_days' => (int) env('REPLACEMENT_RANKING_DAYS', 90),

    /** An open vacancy is reported to the Maintenance Managers again after this many hours. */
    'reminder_hours' => (int) env('VACANCY_REMINDER_HOURS', 24),

];
