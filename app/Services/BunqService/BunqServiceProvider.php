<?php

namespace App\Services\BunqService;

use App\Services\BunqService\Commands\ProcessBunqPaymentsCommand;
use App\Services\BunqService\Commands\ProcessBunqTopUpsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class BunqServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);

            $schedule->command('forus.bunq:process')
                ->everyMinute();
            $schedule->command('forus.bunq:top_up_process')
                ->everyMinute();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            ProcessBunqPaymentsCommand::class,
            ProcessBunqTopUpsCommand::class,
        ]);
    }
}