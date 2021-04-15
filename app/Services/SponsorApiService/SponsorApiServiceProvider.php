<?php

namespace App\Services\SponsorApiService;

use App\Services\SponsorApiService\Commands\RetryActionsFromErrorLogsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class SponsorApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('sponsor_api', function () {
            return new SponsorApi();
        });

        $this->commands([
            RetryActionsFromErrorLogsCommand::class,
        ]);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);

            $schedule->command('sponsor.api.actions:retry')
                ->everyMinute()->withoutOverlapping()->onOneServer();
        });
    }
}
