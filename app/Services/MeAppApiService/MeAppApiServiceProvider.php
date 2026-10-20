<?php

namespace App\Services\MeAppApiService;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MeAppApiServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'me-app-api');
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        Route::middleware([
            'api', 'implementation_key', 'client_key', 'client_version', 'forus_session',
        ])->group(function (): void {
            Route::prefix(trim(Config::get('me-app-api.requester.namespace'), '/') . '/v1')
                ->name('mobile.requester.v1.')
                ->group(function (): void {
                    $this->loadRoutesFrom(__DIR__ . '/routes/requester/meapp-requester-v1.php');
                });

            Route::prefix(trim(Config::get('me-app-api.provider.namespace'), '/') . '/v1')
                ->name('mobile.provider.v1.')
                ->group(function (): void {
                    $this->loadRoutesFrom(__DIR__ . '/routes/provider/meapp-provider-v1.php');
                });
        });
    }
}
