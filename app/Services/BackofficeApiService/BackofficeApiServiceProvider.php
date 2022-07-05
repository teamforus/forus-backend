<?php

namespace App\Services\BackofficeApiService;

use Illuminate\Support\ServiceProvider;

class BackofficeApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
}
