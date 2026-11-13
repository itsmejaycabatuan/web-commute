<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stops the browser keeping a copy of any page served to a signed-in user.
 *
 * Without it the default is `no-cache, private`, which only asks the browser to revalidate. The Back button
 * ignores that and redraws the old page from history, so after logging out the previous /map or
 * /payment/history can still be read. `no-store` makes the browser forget the page.
 *
 * A response that chose its own caching (for example the licence images, `private, max-age=3600`) is left alone.
 */
class NoStoreForSignedInUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Checked after the request ran: logging out must not mark its own redirect, a login must.
        if (! auth()->check()) {
            return $response;
        }

        $cache = $response->headers;

        if ($cache->hasCacheControlDirective('max-age')
            || $cache->hasCacheControlDirective('no-store')
            || $cache->hasCacheControlDirective('public')) {
            return $response;
        }

        $cache->set('Cache-Control', 'no-store, private');
        $cache->set('Pragma', 'no-cache');

        return $response;
    }
}
