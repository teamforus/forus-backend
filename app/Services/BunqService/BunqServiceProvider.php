<?php

namespace App\Services\BunqService;

use Illuminate\Support\ServiceProvider;

class BunqServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }
}