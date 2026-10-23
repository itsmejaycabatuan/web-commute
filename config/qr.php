<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Scan a ride QR code before paying
    |--------------------------------------------------------------------------
    |
    | When true, a commuter has to scan one of the QR codes the Admin generated (they are put up
    | inside the PUJ) before the checkout page opens or a fare can be paid. Set REQUIRE_QR_SCAN=false
    | to switch the rule off.
    |
    */

    'required' => (bool) env('REQUIRE_QR_SCAN', true),

    /** How long a scan stays valid, in minutes, if it has not been used for a payment. */
    'valid_minutes' => (int) env('QR_SCAN_VALID_MINUTES', 15),
];
