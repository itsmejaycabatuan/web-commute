<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayMongo Gateway
    |--------------------------------------------------------------------------
    |
    | This application talks to PayMongo's REST API directly (no SDK — the
    | official paymongo/paymongo-php package is still on a 0.0.0 tag from 2022).
    |
    | The gateway is *disabled by default*. With `enabled => false` the fare flow
    | behaves exactly as it did before PayMongo existed: a gateway method is
    | simply recorded as a declared method and the commuter is sent straight to
    | the receipt. This keeps local development and CI working with no keys.
    |
    | To use the sandbox, create test keys (pk_test_/sk_test_) in the PayMongo
    | dashboard with test mode enabled and set:
    |
    |     PAYMONGO_ENABLED=true
    |     PAYMONGO_SECRET_KEY=sk_test_...
    |     PAYMONGO_PUBLIC_KEY=pk_test_...
    |
    */

    'enabled' => env('PAYMONGO_ENABLED', false),

    // Sandbox (test) keys: https://api.paymongo.com/v1
    // Live (production) keys: https://api.live.paymongo.com/v1
    'secret_key' => env('PAYMONGO_SECRET_KEY'),
    'public_key' => env('PAYMONGO_PUBLIC_KEY'),

    'base_url' => env('PAYMONGO_BASE_URL', 'https://api.paymongo.com/v1'),

    // The secret key is also the HMAC signing key for webhook signatures.
    'webhook_secret' => env('PAYMONGO_WEBHOOK_SECRET'),

    'currency' => env('PAYMONGO_CURRENCY', 'PHP'),

    // PayMongo expects amounts in the currency's minor unit (centavos).
    'minor_unit_factor' => 100,

    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | The success/cancel URLs must be reachable by the commuter's browser.
    | PayMongo server-to-server webhooks must be able to reach `webhook_url`,
    | which is *not* required for the sandbox — the return URL reconciles the
    | payment when a webhook is missed.
    |
    */

    'success_url' => env('PAYMONGO_SUCCESS_URL', '/payment/returned'),
    'cancel_url' => env('PAYMONGO_CANCEL_URL', '/payment/cancelled'),

    'webhook_url' => env('PAYMONGO_WEBHOOK_URL', '/webhooks/paymongo'),

    'statement_descriptor' => env('PAYMONGO_STATEMENT_DESCRIPTOR', 'SMARTCOMMUTE'),

    // How long (seconds) a checkout session stays valid at PayMongo.
    'session_lifetime' => env('PAYMONGO_SESSION_LIFETIME', 3600),

];