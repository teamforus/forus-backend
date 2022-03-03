<?php

namespace App\Services\ProductboardApiService;

use Illuminate\Support\ServiceProvider;

class ProductboardApiServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('productboard_api', function () {
            return new ProductboardApi();
        });
    }
}