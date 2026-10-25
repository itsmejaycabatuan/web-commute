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

    /*
    |--------------------------------------------------------------------------
    | Is the commuter really on a PUJ?
    |--------------------------------------------------------------------------
    |
    | After scanning, the checkout page sends the commuter's position. The server looks for a vehicle that
    | is broadcasting within `nearby_meters` (broadcast no older than `broadcast_fresh_minutes`) and, if it
    | finds one, records that vehicle and the driver on duty on the scan, and the fare inherits both.
    |
    | With `require_nearby_vehicle` on, a scan with no vehicle nearby cannot be used to pay: this is the
    | fare-evasion check (a code photographed and scanned from home, or from a vehicle that is not running).
    | It is off by default because it only works while the driver's phone is broadcasting; turn it on once
    | drivers broadcast reliably (QR_REQUIRE_NEARBY_VEHICLE=true).
    |
    */

    'nearby_meters' => (int) env('QR_NEARBY_METERS', 150),

    'broadcast_fresh_minutes' => (int) env('QR_BROADCAST_FRESH_MINUTES', 5),

    'require_nearby_vehicle' => (bool) env('QR_REQUIRE_NEARBY_VEHICLE', false),
];
