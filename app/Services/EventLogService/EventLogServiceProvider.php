<?php

namespace App\Services\EventLogService;

use App\Services\EventLogService\Interfaces\IEventLogService;
use Illuminate\Support\ServiceProvider;

class EventLogServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(IEventLogService::class, EventLogService::class);
        $this->app->singleton('forus.event_log', static function () {
            return app(IEventLogService::class);
        });

    }
}