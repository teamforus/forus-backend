<?php

namespace App\Console;

use App\Console\Commands\CalculateFundUsersCommand;
use App\Console\Commands\CheckFundConfigCommand;
use App\Console\Commands\CheckFundStateCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CheckFundStateCommand::class,
        CheckFundConfigCommand::class,
        CalculateFundUsersCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('forus.fund:check')
            ->hourlyAt(1);

        $schedule->command('forus.fund.config:check')
            ->everyMinute();

        $schedule->command('forus.fund.users:calculate')
            ->monthly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
