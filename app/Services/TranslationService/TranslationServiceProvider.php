<?php

namespace App\Services\TranslationService;

use App\Services\TranslationService\Console\TranslateCommand;
use App\Services\TranslationService\Console\TranslateStaticCommand;
use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
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

    public function register(): void
    {
        // Register TranslationConfig singleton
        $this->app->singleton(TranslationConfig::class, function () {
            return new TranslationConfig();
        });

        // Register TranslationService singleton
        $this->app->singleton(TranslationService::class, function ($app) {
            $config = $app->make(TranslationConfig::class);
            return new TranslationService($config);
        });

        $this->commands([
            TranslateCommand::class,
            TranslateStaticCommand::class,
        ]);
    }
}
