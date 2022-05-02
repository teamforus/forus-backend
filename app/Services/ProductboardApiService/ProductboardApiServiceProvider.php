<?php

namespace App\Services\ProductboardApiService;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class ProductboardApiServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('productboard', function() {
            $storage = resolve('filesystem')->disk('local');
            $configPath = config('productboard.config_path');

            try {
                $config = json_decode($storage->get($configPath), true);

                if ($config && is_string(Arr::get($config, 'access_token'))) {
                    return new ProductboardApi($config);
                }
            } catch (\Throwable $e) {
                if ($logger = logger()) {
                    $logger->error($e->getMessage());
                }
            }

            return null;
        });
    }
}