<?php

namespace App\Services\QueryCounterService;

use Illuminate\Support\ServiceProvider;

class QueryCounterServiceProvider extends ServiceProvider
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
