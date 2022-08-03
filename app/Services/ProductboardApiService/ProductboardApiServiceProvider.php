<?php

namespace App\Services\ProductboardApiService;

use App\Models\Implementation;
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
        $this->app->singleton('productboard', function() {
            $api_key = Implementation::active()->productboard_api_key ?? Implementation::general()->productboard_api_key;

            return $api_key ? new ProductboardApi($api_key) : null;
        });
    }
}