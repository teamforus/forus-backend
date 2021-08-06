<?php

namespace App\Services\BackofficeApiService;

use App\Services\BackofficeApiService\Commands\SendBackofficeLogsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * Class BackofficeApiServiceProvider
 * @package App\Services\BackofficeApiService
 */
class BackofficeApiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);

            $schedule->command('funds.backoffice:send-logs')
                ->everyMinute()->withoutOverlapping()->onOneServer();
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            SendBackofficeLogsCommand::class,
        ]);
    }
}
