<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Aligns generated URLs (route(), url(), asset()) with the browser URL.
 * Fixes APP_URL mismatches: localhost vs 127.0.0.1, missing /public, ports, etc.
 */
class ForceRootUrlFromRequest
{
    public function handle(Request $request, Closure $next)
    {
        $root = rtrim($request->getSchemeAndHttpHost(), '/').rtrim($request->getBasePath(), '/');
        URL::forceRootUrl($root);

        return $next($request);
    }
}
