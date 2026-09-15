<?php

namespace App\Providers;

use App\Services\ClientIpResolver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $clientIpResolver = app(ClientIpResolver::class);

        /*
         * Dedicated access-request rate limiters.
         *
         * Named limiters prevent unrelated throttled routes from sharing
         * the same anonymous IP bucket.
         */
        RateLimiter::for(
            'access-request-view',
            fn (Request $request) =>
                Limit::perMinute(30)
                    ->by($clientIpResolver->resolve($request))
        );

        RateLimiter::for(
            'access-request-submit',
            fn (Request $request) =>
                Limit::perHour(5)
                    ->by($clientIpResolver->resolve($request))
        );

        RateLimiter::for(
            'access-request-resend',
            fn (Request $request) =>
                Limit::perHour(3)
                    ->by($clientIpResolver->resolve($request))
        );

        RateLimiter::for(
            'access-request-verify',
            fn (Request $request) =>
                Limit::perMinute(6)
                    ->by($clientIpResolver->resolve($request))
        );

        RateLimiter::for(
            'admin-access-request-proof',
            fn (Request $request) =>
                Limit::perMinute(30)
                    ->by(
                        (string) (
                            $request->user()?->getAuthIdentifier()
                            ?? $request->ip()
                        )
                    )
        );

        RateLimiter::for(
            'admin-access-request-decision',
            fn (Request $request) =>
                Limit::perMinute(10)
                    ->by(
                        (string) (
                            $request->user()?->getAuthIdentifier()
                            ?? $request->ip()
                        )
                    )
        );

        RateLimiter::for(
            'admin-access-request-setup-resend',
            fn (Request $request) =>
                Limit::perHour(3)
                    ->by(
                        (string) (
                            $request->user()?->getAuthIdentifier()
                            ?? $request->ip()
                        )
                    )
        );

        /*
         * Render terminates TLS at its proxy and forwards to the container
         * over plain HTTP. Force HTTPS URLs in production.
         */
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
