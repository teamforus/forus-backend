<?php

namespace App\Services\ApiRequestService;

use Illuminate\Support\ServiceProvider;

class ApiRequestServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('api_request', function () {
            return new ApiRequest();
        });
    }
}