<?php

namespace App\Services\SponsorApiService;

use Illuminate\Support\ServiceProvider;

class SponsorApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('sponsor_api', function ($app) {
            return new SponsorApi();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
