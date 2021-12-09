<?php

namespace App\Console;

use App\Console\Commands\BankProcessFundTopUpsCommand;
use App\Console\Commands\BankUpdateContextSessionsCommand;
use App\Console\Commands\BankVoucherTransactionBulksBuildCommand;
use App\Console\Commands\BankVoucherTransactionBulksUpdateStateCommand;
use App\Console\Commands\CalculateFundUsersCommand;
use App\Console\Commands\CheckFundStateCommand;
use App\Console\Commands\CheckProductExpirationCommand;
use App\Console\Commands\ExportPhysicalCardsRequestsCommand;
use App\Console\Commands\MediaCleanupCommand;
use App\Console\Commands\MediaRegenerateCommand;
use App\Console\Commands\NotifyAboutReachedNotificationFundAmount;
use App\Console\Commands\NotifyAboutVoucherExpireCommand;
use App\Console\Commands\Digests\SendAllDigestsCommand;
use App\Console\Commands\Digests\SendDigestMailCommand;
use App\Console\Commands\Digests\SendProviderFundsDigestCommand;
use App\Console\Commands\Digests\SendProviderProductsDigestCommand;
use App\Console\Commands\Digests\SendRequesterDigestCommand;
use App\Console\Commands\Digests\SendSponsorDigestCommand;
use App\Console\Commands\Digests\SendValidatorDigestCommand;
use App\Console\Commands\UpdateFundProviderInvitationExpireStateCommand;
use App\Console\Commands\UpdateNotificationTemplatesCommand;
use App\Console\Commands\UpdateSystemNotificationsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Class Kernel
 * @package App\Console
 */
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
        // SendDigestMailCommand::class,

        // send digest
        SendProviderProductsDigestCommand::class,
        SendProviderFundsDigestCommand::class,
        SendRequesterDigestCommand::class,
        SendValidatorDigestCommand::class,
        SendSponsorDigestCommand::class,

        // send all digests in one command
        SendAllDigestsCommand::class,

        // voucher transaction details
        ExportPhysicalCardsRequestsCommand::class,

        // bank commands
        BankProcessFundTopUpsCommand::class,
        BankUpdateContextSessionsCommand::class,
        BankVoucherTransactionBulksBuildCommand::class,
        BankVoucherTransactionBulksUpdateStateCommand::class,
        UpdateNotificationTemplatesCommand::class,
        UpdateSystemNotificationsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        /**
         * CheckFundStateCommand:
         */
        $schedule->command('forus.fund:check')
            ->hourlyAt(1)->withoutOverlapping()->onOneServer();

        /**
         * CalculateFundUsersCommand:
         */
        $schedule->command('forus.fund.users:calculate')
            ->monthly()->withoutOverlapping()->onOneServer();

        /**
         * NotifyAboutVoucherExpireCommand:
         */
        $schedule->command('forus.voucher:check-expire')
            ->dailyAt('09:00')->withoutOverlapping()->onOneServer();

        /**
         * UpdateFundProviderInvitationExpireStateCommand
         */
        $schedule->command('forus.funds.provider_invitations:check-expire')
            ->everyFifteenMinutes()->withoutOverlapping()->onOneServer();

        /**
         * NotifyAboutReachedNotificationFundAmount
         */
        $schedule->command('forus.fund:check-amount')
            ->cron('0 */8 * * *')->withoutOverlapping()->onOneServer();

        /**
         * CheckProductExpirationCommand
         */
        $schedule->command('forus.product.expiration:check')
            ->daily()->withoutOverlapping()->onOneServer();

        /**
         * CheckActionExpirationCommand
         */
        $schedule->command('forus.action.expiration:check')
            ->daily()->withoutOverlapping()->onOneServer();

        $this->scheduleBank($schedule);
        $this->scheduleDigest($schedule);
        $this->scheduleQueue($schedule);
    }

    /**
     * @param Schedule $schedule
     */
    public function scheduleBank(Schedule $schedule): void
    {
        /**
         * BankProcessFundTopUpsCommand
         */
        $schedule->command('bank:process-top-ups')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        /**
         * BankUpdateContextSessionsCommand
         */
        $schedule->command('bank:update-context-sessions')
            ->dailyAt("03:00")
            ->withoutOverlapping()
            ->onOneServer();

        /**
         * BankVoucherTransactionBulksBuildCommand
         */
        $schedule->command('bank:bulks-build')
            ->dailyAt(env('BANK_DAILY_BULK_BUILD_TIME', '09:00'))
            ->withoutOverlapping()
            ->onOneServer();

        /**
         * BankVoucherTransactionBulksUpdateStateCommand
         */
        $schedule->command('bank:bulks-update')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();
    }

    /**
     * @param Schedule $schedule
     */
    public function scheduleDigest(Schedule $schedule): void
    {
        /**
         * DigIdSessionsCleanupCommand
         */
        $schedule->command('digid:session-clean')
            ->everyMinute()->withoutOverlapping()->onOneServer();

        /**
         * Digests
         */
        $schedule->command('forus.digest.validator:send')
            ->dailyAt("18:00")->withoutOverlapping()->onOneServer();

        $schedule->command('forus.digest.provider_funds:send')
            ->dailyAt("18:00")->withoutOverlapping()->onOneServer();

        $schedule->command('forus.digest.provider_products:send')
            ->dailyAt("18:00")->withoutOverlapping()->onOneServer();

        $schedule->command('forus.digest.provider_reservations:send')
            ->weeklyOn(1, "18:00")->withoutOverlapping()->onOneServer();

        $schedule->command('forus.digest.sponsor:send')
            ->dailyAt("18:00")->withoutOverlapping()->onOneServer();

        // $schedule->command('forus.digest.requester:send')
        //     ->monthlyOn(1, "18:00")->withoutOverlapping()->onOneServer();
    }

    /**
     * @param Schedule $schedule
     */
    public function scheduleQueue(Schedule $schedule): void
    {
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
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
