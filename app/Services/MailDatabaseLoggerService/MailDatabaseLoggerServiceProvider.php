<?php

namespace App\Services\MailDatabaseLoggerService;

use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class MailDatabaseLoggerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        if (!App::isProduction() || env('LOG_EMAILS_IN_PROD', FALSE)) {
            $this->app->register(MailDatabaseLoggerEventServiceProvider::class);
        }
    }
}