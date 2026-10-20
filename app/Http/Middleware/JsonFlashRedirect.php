<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;

/**
 * Lets the same controllers serve classic form posts and fetch() calls.
 *
 * Controllers answer with `back()->with('success', ...)`. For a request that
 * asks for JSON (the `data-ajax` forms) that redirect is turned into a JSON
 * body carrying the flash message, so the page can show a toast, keep a modal
 * open on a failure, and decide for itself where to go next. Validation
 * failures already come back as 422 JSON from Laravel.
 */
class JsonFlashRedirect
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $request->expectsJson() || ! $response instanceof RedirectResponse) {
            return $response;
        }

        $session = $request->session();

        $errors = $session->get('errors');
        if ($errors instanceof ViewErrorBag && $errors->any()) {
            $session->forget('errors');

            return response()->json([
                'message' => $errors->first(),
                'errors' => $errors->getBag('default')->messages(),
            ], 422);
        }

        $error = $session->get('error');
        $warning = $session->get('warning');
        $message = $session->get('success') ?? $session->get('message');

        $session->forget(['success', 'error', 'warning', 'message']);

        if ($error) {
            return response()->json([
                'message' => $error,
                'type' => 'error',
                'redirect' => $response->getTargetUrl(),
            ], 409);
        }

        return response()->json([
            'message' => $message ?? $warning,
            'type' => $message ? 'success' : ($warning ? 'warning' : 'success'),
            'redirect' => $response->getTargetUrl(),
        ]);
    }
}
