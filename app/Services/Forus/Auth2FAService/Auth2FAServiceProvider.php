<?php

namespace App\Services\Forus\Auth2FAService;

use Illuminate\Support\ServiceProvider;

class Auth2FAServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->app->singleton('forus.services.auth2fa', function () {
            return app()->make(Auth2FAService::class);
        });
    }
}
