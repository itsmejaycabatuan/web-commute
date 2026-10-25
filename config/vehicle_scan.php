<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scan the registration papers to fill in a new vehicle
    |--------------------------------------------------------------------------
    |
    | The Maintenance Manager presses "Scan with phone" on the Add Vehicle form, scans the QR code shown on
    | the screen with a phone, and photographs the vehicle's OR/CR. The photo is read on THIS server with
    | Tesseract (nothing is sent to a third party), and the details come back to the form for the manager
    | to check before saving. The photo is deleted once the vehicle is saved, or after `ttl_minutes`.
    |
    | Needs the `tesseract-ocr` package on the server (sudo apt-get install -y tesseract-ocr).
    |
    */

    'enabled' => (bool) env('VEHICLE_SCAN_ENABLED', true),

    'tesseract_binary' => env('TESSERACT_BINARY', 'tesseract'),

    /** How long a scan session (the QR code, the uploaded photos) stays valid. */
    'ttl_minutes' => (int) env('VEHICLE_SCAN_TTL_MINUTES', 15),

    /** Photos per scan: the CR and, optionally, the OR. */
    'max_files' => 2,

    'max_file_kb' => (int) env('VEHICLE_SCAN_MAX_FILE_KB', 12288),

    /** Longest side, in pixels, a photo is scaled to before reading. Phone photos are bigger than needed. */
    'max_side' => (int) env('VEHICLE_SCAN_MAX_SIDE', 3200),

    /** Pages smaller than this on their long side are enlarged first: Tesseract wants ~300 dpi text. */
    'min_side' => (int) env('VEHICLE_SCAN_MIN_SIDE', 2400),

    /** Seconds one Tesseract pass may run. */
    'pass_timeout' => (int) env('VEHICLE_SCAN_PASS_TIMEOUT', 60),

    /** Threads Tesseract may use per pass (OMP_THREAD_LIMIT). */
    'threads' => (int) env('VEHICLE_SCAN_THREADS', 2),

    /** A value read below this confidence (0-100) is left blank rather than guessed. */
    'min_confidence' => (int) env('VEHICLE_SCAN_MIN_CONFIDENCE', 45),

    /** A value read below this confidence (0-100), or one that needed a character corrected, is marked "check". */
    'check_confidence' => (int) env('VEHICLE_SCAN_CHECK_CONFIDENCE', 80),

];
