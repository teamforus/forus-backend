<?php

namespace App\Services\DigIdService;

use App\Services\DigIdService\Console\Commands\DigIdSessionsCleanupCommand;
use App\Services\DigIdService\Repositories\DigIdRepo;
use App\Services\DigIdService\Repositories\Interfaces\IDigIdRepo;
use Illuminate\Support\ServiceProvider;

/**
 * Class DigIdServiceProvider
 * @package App\Services\DigIdService
 */
class DigIdServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->commands([
            DigIdSessionsCleanupCommand::class
        ]);

        $this->app->bind(IDigIdRepo::class, DigIdRepo::class);
        $this->app->bind('digId', function () {
            return app(IDigIdRepo::class);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {}
}