<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'track/*/update',  // This disables CSRF for all tracking update routes
        'api/*',           // Or disable for all API routes if you move them
        // Driver GPS pings. Authorisation is enforced in the controller
        // (driver role + vehicle assigned to that driver), not by the token.
        'track/vehicle/broadcast',
    ];
}
