<?php

namespace App\Services\Forus\TestData;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class TestDataProvider extends ServiceProvider
{
    public function boot() {}

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('forus.services.test_data', function () {
            return new TestData();
        });
    }
}