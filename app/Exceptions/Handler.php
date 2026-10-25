<?php

namespace App\Exceptions;

use App\Support\SessionExpiry;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // An expired session makes the CSRF token invalid, so the first form post after a
        // long idle used to land on the "419 Page Expired" screen. A signed-out visitor is
        // taken to the login page instead (with a notice); a signed-in one keeps the 419.
        $this->renderable(function (HttpException $e, $request) {
            if ($e->getStatusCode() !== 419 || ! $e->getPrevious() instanceof TokenMismatchException) {
                return null;
            }

            if ($request->user()) {
                return null;
            }

            $expired = SessionExpiry::wasSignedIn($request);
            $message = $expired ? SessionExpiry::EXPIRED_MESSAGE : SessionExpiry::PAGE_EXPIRED_MESSAGE;

            if ($request->expectsJson()) {
                return $this->sessionExpiredJson($message, $expired);
            }

            // Someone who was never signed in (a login or register form left open) goes back
            // to the page they were on; someone who was signed in goes to the login page.
            $response = $expired
                ? redirect()->route('login')
                : redirect()->back(302, [], route('login'));

            return $response->with('warning', $message);
        });
    }

    /**
     * A signed-out visitor hitting a protected page lands on the login page; if they were
     * signed in a moment ago the page says why.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        $expired = SessionExpiry::wasSignedIn($request);

        if ($request->expectsJson()) {
            return $this->sessionExpiredJson($expired ? SessionExpiry::EXPIRED_MESSAGE : 'Please sign in to continue.', $expired);
        }

        $response = redirect()->guest($exception->redirectTo() ?? route('login'));

        return $expired ? $response->with('warning', SessionExpiry::EXPIRED_MESSAGE) : $response;
    }

    private function sessionExpiredJson(string $message, bool $expired)
    {
        return response()->json([
            'message' => $message,
            'session_expired' => $expired,
            // Relative on purpose: an absolute URL breaks behind an https tunnel (see AppForms).
            'redirect' => route('login', [], false),
        ], 401);
    }
}
