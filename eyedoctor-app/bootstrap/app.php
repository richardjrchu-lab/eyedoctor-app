<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Never flash professional registration numbers back into the
         * session after validation failures.
         */
        $exceptions->dontFlash([
            'license_registration_number',
        ]);

        // An expired CSRF token otherwise renders a blank "419 PAGE EXPIRED"
        // with no explanation and no way forward. A clinician who leaves a tab
        // open on a shared terminal has no reason to know that reloading fixes
        // it. Send them back to login with a sentence that says what happened.
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