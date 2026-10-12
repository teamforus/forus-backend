<?php

namespace App\Services\OpenIdService;

use App\Services\OpenIdService\Console\Commands\OpenIdSessionsCleanupCommand;
use Illuminate\Support\ServiceProvider;

class OpenIdServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands(OpenIdSessionsCleanupCommand::class);
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        $this->app->bind(OpenIdService::class, OpenIdService::class);
    }
}
