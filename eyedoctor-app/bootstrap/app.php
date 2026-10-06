<?php

use App\Http\Middleware\EnsureLegalAccepted;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Render terminates HTTPS at its edge and forwards the request to
         * this container over HTTP. Trust only the original scheme header
         * needed to reconstruct HTTPS requests correctly.
         *
         * This keeps signed URL verification consistent without changing
         * Laravel's interpretation of forwarded client IPs, hosts, or ports.
         */
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'legal.accepted' => EnsureLegalAccepted::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Never flash professional registration numbers back into the
         * session after validation failures.
         */
        $exceptions->dontFlash([
            'license_registration_number',
            'cf-turnstile-response',
        ]);

        /*
         * An expired CSRF token otherwise renders a blank 419 page with no
         * useful explanation. Send the clinician back to login instead.
         */
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'detail' => 'Your session expired. Please refresh the page and sign in again.',
                ], 419);
            }

            return redirect()
                ->route('login')
                ->with('status', 'Your session expired. Please sign in again.');
        });
    })->create();