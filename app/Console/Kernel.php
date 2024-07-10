<?php

namespace App\Console;

use App\Console\Commands\BankConnectionExpirationNotifyCommand;
use App\Console\Commands\BankProcessFundTopUpsCommand;
use App\Console\Commands\BankUpdateContextSessionsCommand;
use App\Console\Commands\BankVoucherTransactionBulksBuildCommand;
use App\Console\Commands\BankVoucherTransactionBulksUpdateStateCommand;
use App\Console\Commands\BankVoucherTransactionProcessZeroAmountCommand;
use App\Console\Commands\BankConnections\BankConnectionsInspectCommand;
use App\Console\Commands\CalculateFundUsersCommand;
use App\Console\Commands\CheckFundStateCommand;
use App\Console\Commands\CheckProductExpirationCommand;
use App\Console\Commands\Digests\SendProviderReservationsDigestCommand;
use App\Console\Commands\ExportPhysicalCardsRequestsCommand;
use App\Console\Commands\MediaCleanupCommand;
use App\Console\Commands\MediaRegenerateCommand;
use App\Console\Commands\NotifyAboutReachedNotificationFundAmount;
use App\Console\Commands\NotifyAboutVoucherExpireCommand;
use App\Console\Commands\Digests\SendAllDigestsCommand;
use App\Console\Commands\Digests\SendProviderFundsDigestCommand;
use App\Console\Commands\Digests\SendProviderProductsDigestCommand;
use App\Console\Commands\Digests\SendRequesterDigestCommand;
use App\Console\Commands\Digests\SendSponsorDigestCommand;
use App\Console\Commands\Digests\SendValidatorDigestCommand;
use App\Console\Commands\PhysicalCards\MigratePhysicalCardsCommand;
use App\Console\Commands\ReservationExtraPaymentExpireCommand;
use App\Console\Commands\UpdateFundProviderInvitationExpireStateCommand;
use App\Console\Commands\UpdateNotificationTemplatesCommand;
use App\Console\Commands\UpdateProductCategoriesCommand;
use App\Console\Commands\UpdateRolesCommand;
use App\Console\Commands\UpdateSystemNotificationsCommand;
use App\Services\Forus\Session\Commands\UpdateSessionsExpirationCommand;
use App\Services\BackofficeApiService\Commands\SendBackofficeLogsCommand;
use App\Services\MailDatabaseLoggerService\Commands\MailDatabaseLoggerClearUnusedAttachmentsCommand;
use App\Services\MollieService\Commands\UpdateCompletedMollieConnectionsCommand;
use App\Services\MollieService\Commands\UpdatePendingMollieConnectionsCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Config;

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
        BankVoucherTransactionProcessZeroAmountCommand::class,
        BankConnectionExpirationNotifyCommand::class,

        // system notifications and notification templates commands
        UpdateNotificationTemplatesCommand::class,
        UpdateSystemNotificationsCommand::class,

        // physical cards
        MigratePhysicalCardsCommand::class,
        UpdateSessionsExpirationCommand::class,
        SendBackofficeLogsCommand::class,

        // bank connections
        BankConnectionsInspectCommand::class,

        // seeders
        UpdateProductCategoriesCommand::class,
        UpdateRolesCommand::class,

        // mollie
        UpdateCompletedMollieConnectionsCommand::class,
        UpdatePendingMollieConnectionsCommand::class,

        // extra payments
        ReservationExtraPaymentExpireCommand::class,

        // Email logger
        MailDatabaseLoggerClearUnusedAttachmentsCommand::class,
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
        $this->scheduleBackoffice($schedule);
        $this->scheduleQueue($schedule);
        $this->scheduleAuthExpiration($schedule);
        $this->scheduleMollieConnections($schedule);
        $this->scheduleReservationExtraPayments($schedule);
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
            ->hourly()
            ->onOneServer()
            ->withoutOverlapping();

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
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        /**
         * BankVoucherTransactionProcessZeroAmountCommand:
         */
        $schedule->command('bank:process-zero-amount')
            ->dailyAt(Config::get('forus.kernel.bank_daily_bulk_build_time', '09:00'))
            ->withoutOverlapping()
            ->onOneServer();

        /**
         * BankVoucherTransactionBulksUpdateStateCommand
         */
        $schedule->command('bank:bulks-update')
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        /**
         * BankConnectionExpirationNotifyCommand:
         */
        $schedule->command('bank:notify-connection-expiration')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->onOneServer();
    }

    /**
     * @param Schedule $schedule
     */
    public function scheduleDigest(Schedule $schedule): void
    {
        if (Config::get('forus.kernel.disable_digest', false)) {
            return;
        }

        /**
         * DigIdSessionsCleanupCommand
         */
        $schedule->command('digid:session-clean')
            ->everyMinute()->withoutOverlapping()->onOneServer();

        /**
         * Digests
         */
        $schedule->command(SendValidatorDigestCommand::class)
            ->dailyAt("18:00")->withoutOverlapping()->onOneServer();

        $schedule->command(SendProviderFundsDigestCommand::class)
            ->dailyAt("18:00")->withoutOverlapping()->onOneServer();

        $schedule->command(SendProviderProductsDigestCommand::class)
            ->dailyAt("18:00")->withoutOverlapping()->onOneServer();

        $schedule->command(SendProviderReservationsDigestCommand::class)
            ->weeklyOn(1, "18:00")->withoutOverlapping()->onOneServer();

        $schedule->command(SendSponsorDigestCommand::class)
            ->dailyAt("18:00")->withoutOverlapping()->onOneServer();

        // $schedule->command(SendRequesterDigestCommand::class)
        //     ->monthlyOn(1, "18:00")->withoutOverlapping()->onOneServer();
    }

    /**
     * @param Schedule $schedule
     */
    public function scheduleQueue(Schedule $schedule): void
    {
        // use cron to send email/notifications
        if (Config::get('forus.kernel.queue_use_cron', false)) {
            $emailQueue = Config::get('forus.kernel.email_queue_name', 'emails');
            $notificationsQueue = Config::get('forus.kernel.notifications_queue_name', 'push_notifications');

            $schedule
                ->command('queue:work --queue=' . $emailQueue)
                ->everyMinute()->withoutOverlapping()->onOneServer();

            $schedule
                ->command('queue:work --queue=' . $notificationsQueue)
                ->everyMinute()->withoutOverlapping()->onOneServer();
        }
    }

    /**
     * @param Schedule $schedule
     * @return void
     */
    private function scheduleAuthExpiration(Schedule $schedule): void
    {
        if (Config::get('disable_auth_expiration', false)) {
            return;
        }

        /**
         * UpdateSessionsExpirationCommand
         */
        $schedule->command('auth_sessions:update-expiration --force')
            ->withoutOverlapping()
            ->everyMinute()
            ->onOneServer();
    }

    /**
     * @param Schedule $schedule
     * @return void
     */
    private function scheduleBackoffice(Schedule $schedule): void
    {
        /**
         * SendBackofficeLogsCommand
         */
        $schedule->command('funds.backoffice:send-logs')
            ->withoutOverlapping()
            ->everyMinute()
            ->onOneServer();
    }

    /**
     * @param Schedule $schedule
     */
    private function scheduleMollieConnections(Schedule $schedule): void
    {
        /**
         * UpdatePendingMollieConnectionsCommand
         */
        $schedule->command('mollie:update-pending-connections')
            ->hourly()
            ->onOneServer()
            ->withoutOverlapping();

        /**
         * UpdateCompletedMollieConnectionsCommand
         */
        $schedule->command('mollie:update-completed-connections')
            ->dailyAt("09:00")
            ->withoutOverlapping()
            ->onOneServer();
    }

    /**
     * @param Schedule $schedule
     * @return void
     */
    private function scheduleReservationExtraPayments(Schedule $schedule): void
    {
        /**
         * UpdateCompletedMollieConnectionsCommand
         */
        $schedule->command('reservation:extra-payments-expire')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();
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
