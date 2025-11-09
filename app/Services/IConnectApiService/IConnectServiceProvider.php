<?php

namespace App\Services\IConnectApiService;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class IConnectServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->app->environment('testing')) {
            Http::fake([
                Str::finish(IConnect::URL_SANDBOX, '/') . '*' => fn () => Http::response(
                    config('forus.person_bsn.test_response', [])
                ),
            ]);
        }
    }
}
