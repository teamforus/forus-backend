<?php

namespace App\Services\MailDatabaseLoggerService;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
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

        if (!App::isProduction() || Config::get('forus.mail.log_production')) {
            $this->app->register(MailDatabaseLoggerEventServiceProvider::class);
        }
    }
}
