<?php

namespace App\Services\KeyPairGeneratorService;

use Illuminate\Support\ServiceProvider;

class KeyPairGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('key_pair_generator', function () {
            return new KeyPairGenerator();
        });
    }
}