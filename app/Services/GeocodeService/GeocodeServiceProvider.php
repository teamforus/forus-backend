<?php

namespace App\Services\GeocodeService;

use Illuminate\Support\ServiceProvider;

/**
 * Class GeocodeServiceProvider
 * @package App\Services\GeocodeService
 */
class GeocodeServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('geocode_api', function () {
            return new GeocodeService(
                env("GOOGLE_API_KEY")
            );
        });
    }
}