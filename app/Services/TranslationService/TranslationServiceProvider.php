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
        $translationBuilder = function ($app) {
            $config = $app->make(TranslationConfig::class);
            return new TranslationService($config);
        };

        $translationConfigBuilder = function () {
            return new TranslationConfig();
        };

        // Register TranslationConfig singleton
        $this->app->singleton(TranslationConfig::class, $translationConfigBuilder);
        $this->app->singleton('translation_service.config', $translationConfigBuilder);

        // Register TranslationService singleton
        $this->app->singleton(TranslationService::class, $translationBuilder);
        $this->app->singleton('translation_service', $translationBuilder);

        $this->commands([
            TranslateCommand::class,
            TranslateStaticCommand::class,
        ]);
    }
}
