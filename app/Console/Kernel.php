<?php

namespace App\Console;

use App\Console\Commands\CalculateFundUsersCommand;
use App\Console\Commands\CheckFundConfigCommand;
use App\Console\Commands\CheckFundStateCommand;
use App\Console\Commands\CheckProductExpirationCommand;
use App\Console\Commands\CheckVoucherExpirationCommand;
use App\Console\Commands\MediaCleanupCommand;
use App\Console\Commands\MediaRegenerateCommand;
use App\Console\Commands\NotifyAboutReachedNotificationFundAmount;
use App\Console\Commands\NotifyAboutVoucherExpireCommand;
use App\Console\Commands\SendDigestMailCommand;
use App\Console\Commands\SendProviderFundsDailyDigestCommand;
use App\Console\Commands\SendProviderProductsDailyDigestCommand;
use App\Console\Commands\SendRequesterWeeklyDigestCommand;
use App\Console\Commands\SendSponsorDailyDigestCommand;
use App\Console\Commands\SendValidatorDailyDigestCommand;
use App\Console\Commands\UpdateFundProviderInvitationExpireStateCommand;
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
        // media
        MediaCleanupCommand::class,
        MediaRegenerateCommand::class,

        // funds
        CheckFundStateCommand::class,
        CheckFundConfigCommand::class,

        // statistics
        CalculateFundUsersCommand::class,

        // notifications
        NotifyAboutVoucherExpireCommand::class,
        NotifyAboutReachedNotificationFundAmount::class,

        // provider invitations
        UpdateFundProviderInvitationExpireStateCommand::class,

        // product expiration
        CheckProductExpirationCommand::class,

        // voucher expiration
        CheckVoucherExpirationCommand::class,

        // voucher expiration
        // SendDigestMailCommand::class,

        // send digest
        SendProviderProductsDailyDigestCommand::class,
        SendProviderFundsDailyDigestCommand::class,
        SendRequesterWeeklyDigestCommand::class,
        SendValidatorDailyDigestCommand::class,
        SendSponsorDailyDigestCommand::class,
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
            ->hourlyAt(1)->withoutOverlapping()->onOneServer();

        $schedule->command('forus.fund.config:check')
            ->everyMinute()->withoutOverlapping()->onOneServer();

        $schedule->command('forus.fund.users:calculate')
            ->monthly()->withoutOverlapping()->onOneServer();

        $schedule->command('forus.voucher:check-expire')
            ->dailyAt('09:00')->withoutOverlapping()->onOneServer();

        $schedule->command('forus.funds.provider_invitations:check-expire')
            ->everyFifteenMinutes()->withoutOverlapping()->onOneServer();

        $schedule->command('forus.fund:check-amount')
            ->cron('0 */8 * * *')->withoutOverlapping()->onOneServer();
  
        $schedule->command('digid:session-clean')
            ->everyMinute()->withoutOverlapping()->onOneServer();

        $schedule->command('forus.product.expiration:check')
            ->daily()->withoutOverlapping()->onOneServer();

        /**
         * Digests
         */
        $schedule->command('forus.digest.validator:send')
            ->dailyAt("18:00")->withoutOverlapping()->onOneServer();

        $schedule->command('forus.digest.provider_funds:send')
            ->dailyAt("18:00")->withoutOverlapping()->onOneServer();

        $schedule->command('forus.digest.provider_products:send')
            ->dailyAt("18:00")->withoutOverlapping()->onOneServer();

        $schedule->command('forus.digest.sponsor:send')
            ->dailyAt("18:00")->withoutOverlapping()->onOneServer();

        $schedule->command('forus.digest.requester:send')
            ->weeklyOn(5, "18:00")->withoutOverlapping()->onOneServer();


        // use cron to send email/notifications
        if (env('QUEUE_USE_CRON', false)) {
            $schedule->command('queue:work --queue=' . env('EMAIL_QUEUE_NAME', 'emails'))
                ->everyMinute()->withoutOverlapping()->onOneServer();

            $schedule->command('queue:work --queue=' . env('NOTIFICATIONS_QUEUE_NAME', 'push_notifications'))
                ->everyMinute()->withoutOverlapping()->onOneServer();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        /** @noinspection PhpIncludeInspection */
        require base_path('routes/console.php');
    }
}
