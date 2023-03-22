<?php

namespace App\Services\DigIdService;

use App\Services\DigIdService\Console\Commands\DigIdSessionsCleanupCommand;
use App\Services\DigIdService\Repositories\DigIdCgiRepo;
use App\Services\DigIdService\Repositories\Interfaces\DigIdRepo;
use Illuminate\Support\ServiceProvider;

class DigIdServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands(DigIdSessionsCleanupCommand::class);
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        $this->app->bind(DigIdRepo::class, DigIdCgiRepo::class);
        $this->app->bind('digId', fn () => resolve(DigIdRepo::class));
    }
}