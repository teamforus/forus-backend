<?php

namespace App\Services\DCPService;

use Illuminate\Support\ServiceProvider;

class DCPServiceProvider extends ServiceProvider
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
