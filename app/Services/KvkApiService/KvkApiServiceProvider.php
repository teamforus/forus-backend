<?php

namespace App\Services\KvkApiService;

use Illuminate\Support\ServiceProvider;

class KvkApiServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('kvk_api', function () {
            return new KvkApi(
                env("KVK_API_DEBUG", false),
                env("KVK_API_KEY"),
                // only for local development, disabled by default
                env("KVK_API_DISABLE_SSL_VERIFICATION", false)
            );
        });
    }
}