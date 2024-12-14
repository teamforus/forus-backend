<?php

namespace App\Services\GeocodeService;

use Illuminate\Support\ServiceProvider;

class GeocodeServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('geocode_api', function () {
            return new GeocodeService(env("GOOGLE_API_KEY"));
        });
    }
}