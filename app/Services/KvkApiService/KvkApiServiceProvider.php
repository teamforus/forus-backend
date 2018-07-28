<?php

namespace App\Services\KvkApiService;

use Illuminate\Support\ServiceProvider;
use App\Services\KvkApiService\KvkApi;

class KvkApiServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('kvk_api', function () {
            return new KvkApi(config('kvk_api.key'));
        });
    }
}