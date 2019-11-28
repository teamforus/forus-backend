<?php

namespace App\Services\DigIdService;

use App\Services\BunqService\Commands\ProcessBunqCheckBunqMeTabsCommand;
use App\Services\BunqService\Commands\ProcessBunqPaymentsCommand;
use App\Services\BunqService\Commands\ProcessBunqSyncIdealIssuersCommand;
use App\Services\BunqService\Commands\ProcessBunqTopUpsCommand;
use App\Services\DigIdService\Repositories\DigIdRepo;
use App\Services\DigIdService\Repositories\Interfaces\IDigIdRepo;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class DigIdServiceProvider extends ServiceProvider
{
    public function boot()
    {
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
    public function register()
    {

    }
}