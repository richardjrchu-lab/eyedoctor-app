<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Render terminates TLS at its proxy and forwards to the container
        // over plain HTTP, so Laravel sees an insecure request and generates
        // http:// asset URLs. The browser then blocks them as mixed content
        // on an https:// page -- which is why the CSS and JS never load.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}