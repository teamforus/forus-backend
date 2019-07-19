<?php

namespace App\Services\BunqService;

use App\Services\BunqService\Commands\ProcessBunqCheckBunqMeTabsCommand;
use App\Services\BunqService\Commands\ProcessBunqPaymentsCommand;
use App\Services\BunqService\Commands\ProcessBunqSyncIdealIssuersCommand;
use App\Services\BunqService\Commands\ProcessBunqTopUpsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class BunqServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        $this->app->booted(function () {
            $schedule = app(Schedule::class);

            $schedule->command('forus.bunq:process')
                ->everyMinute();
            $schedule->command('forus.bunq:top_up_process')
                ->everyMinute();
            $schedule->command('forus.bunq:check_bunq_me_tabs')
                ->everyMinute();
            $schedule->command('forus.bunq:sync_ideal_issuers')
                ->dailyAt("01:00");
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
            ProcessBunqSyncIdealIssuersCommand::class,
            ProcessBunqCheckBunqMeTabsCommand::class,
            ProcessBunqPaymentsCommand::class,
            ProcessBunqTopUpsCommand::class,
        ]);
    }
}