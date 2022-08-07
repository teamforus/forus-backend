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
            $api_key = Implementation::active()->getProductboardApiKey();

            return $api_key ? new ProductboardApi($api_key) : null;
        });
    }
}