<?php

namespace App\Listeners;

use App\Events\Funds\FundArchivedEvent;
use App\Events\Funds\FundBalanceLowEvent;
use App\Events\Funds\FundBalanceSuppliedEvent;
use App\Events\Funds\FundEndedEvent;
use App\Events\Funds\FundCreatedEvent;
use App\Events\Funds\FundExpiringEvent;
use App\Events\Funds\FundProductAddedEvent;
use App\Events\Funds\FundProductApprovedEvent;
use App\Events\Funds\FundProductRevokedEvent;
use App\Events\Funds\FundProviderApplied;
use App\Events\Funds\FundProviderChatMessageEvent;
use App\Events\Funds\FundProviderInvitedEvent;
use App\Events\Funds\FundStartedEvent;
use App\Events\Funds\FundUnArchivedEvent;
use App\Events\Funds\FundUpdatedEvent;
use App\Events\Funds\FundVouchersExportedEvent;
use App\Mail\Forus\ForusFundCreatedMail;
use App\Mail\Funds\FundBalanceWarningMail;
use App\Mail\Funds\ProviderInvitationMail;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Notifications\Identities\Fund\IdentityRequesterProductRevokedNotification;
use App\Notifications\Organizations\FundProviders\FundProviderFundEndedNotification;
use App\Notifications\Organizations\FundProviders\FundProviderFundExpiringNotification;
use App\Notifications\Organizations\FundProviders\FundProviderFundStartedNotification;
use App\Notifications\Organizations\Funds\BalanceLowNotification;
use App\Notifications\Organizations\Funds\BalanceSuppliedNotification;
use App\Notifications\Organizations\Funds\FundArchivedNotification;
use App\Notifications\Organizations\Funds\FundCreatedNotification;
use App\Notifications\Organizations\Funds\FundEndedNotification;
use App\Notifications\Organizations\Funds\FundExpiringNotification;
use App\Notifications\Organizations\Funds\FundProductAddedNotification;
use App\Notifications\Organizations\Funds\FundProviderAppliedNotification;
use App\Notifications\Organizations\Funds\FundProviderChatMessageNotification;
use App\Notifications\Organizations\Funds\FundStartedNotification;
use App\Notifications\Identities\Fund\IdentityRequesterProductAddedNotification;
use App\Notifications\Identities\Fund\IdentityRequesterProductApprovedNotification;
use App\Notifications\Organizations\Funds\FundUnArchivedNotification;
use App\Scopes\Builders\FundProviderQuery;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Config;

class FundSubscriber
{
    private mixed $notificationService;

    /**
     * @param Fund $fund
     * @param array $extraModels
     * @return array
     */
    private function getFundLogModels(Fund $fund, array $extraModels = []): array
    {
        return array_merge([
            'fund' => $fund,
            'sponsor' => $fund->organization,
            'implementation' => $fund->getImplementation(),
        ], $extraModels);
    }

    /**
     * FundSubscriber constructor.
     */
    public function __construct()
    {
        $this->notificationService = resolve('forus.services.notification');
    }

    /**
     * @param FundCreatedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundCreated(FundCreatedEvent $event): void {
        $fund = $event->getFund();

        $fund->update([
            'description_text' => $fund->descriptionToText(),
        ]);

        FundCreatedNotification::send($fund->log(
            $fund::EVENT_CREATED,
            $this->getFundLogModels($fund)
        ));

        if ($email = Config::get('forus.notification_mails.fund_created', false)) {
            $this->notificationService->sendSystemMail($email, new ForusFundCreatedMail([
                'fund_name' => $fund->name,
                'sponsor_name' => $fund->organization->name,
            ]));
        }
    }

    /**
     * @param FundUpdatedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundUpdated(FundUpdatedEvent $event): void {
        $fund = $event->getFund();

        $fund->update([
            'description_text' => $fund->descriptionToText(),
        ]);
    }

    /**
     * @param FundStartedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundStarted(FundStartedEvent $event): void {
        $fund = $event->getFund();

        FundStartedNotification::send($fund->log(
            $fund::EVENT_FUND_STARTED,
            $this->getFundLogModels($fund)
        ));

        /** @var FundProvider[] $fundProviders */
        $fundProviders = FundProviderQuery::whereApprovedForFundsFilter(
            $fund->providers()->getQuery(), $fund->id
        )->get();

        foreach ($fundProviders as $fundProvider) {
            FundProviderFundStartedNotification::send(
                $fundProvider->log($fundProvider::EVENT_FUND_STARTED, $this->getFundLogModels($fund, [
                    'provider' => $fundProvider->organization,
                ]))
            );
        }
    }

    /**
     * @param FundExpiringEvent $event
     * @noinspection PhpUnused
     */
    public function onFundExpiring(FundExpiringEvent $event): void {
        $fund = $event->getFund();

        FundExpiringNotification::send($fund->log(
            $fund::EVENT_FUND_EXPIRING,
            $this->getFundLogModels($fund)
        ));

        /** @var FundProvider[] $fundProviders */
        $fundProviders = FundProviderQuery::whereApprovedForFundsFilter(
            $fund->providers()->getQuery(), $fund->id
        )->get();

        foreach ($fundProviders as $fundProvider) {
            $eventLog = $fundProvider->log($fundProvider::EVENT_FUND_EXPIRING, $this->getFundLogModels($fund, [
                'provider' => $fundProvider->organization,
            ]));

            FundProviderFundExpiringNotification::send($eventLog);
        }
    }

    /**
     * @param FundEndedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundEnded(FundEndedEvent $event): void
    {
        $fund = $event->getFund();
        $eventLog = $fund->log($fund::EVENT_FUND_ENDED, $this->getFundLogModels($fund));

        FundEndedNotification::send($eventLog);

        $query = $fund->providers()->getQuery();
        $fundProviders = FundProviderQuery::whereApprovedForFundsFilter($query, $fund->id)->get();

        /** @var FundProvider[] $fundProviders */
        foreach ($fundProviders as $fundProvider) {
            $eventLog = $fundProvider->log($fundProvider::EVENT_FUND_ENDED, $this->getFundLogModels($fund, [
                'provider' => $fundProvider->organization,
            ]));

            FundProviderFundEndedNotification::send($eventLog);
        }
    }

    /**
     * @param FundProviderApplied $event
     * @noinspection PhpUnused
     */
    public function onFundProviderApplied(FundProviderApplied $event): void
    {
        $fund = $event->getFund();
        $fundProvider = $event->getFundProvider();

        $eventLog = $fund->log($fund::EVENT_PROVIDER_APPLIED, $this->getFundLogModels($fund, [
            'provider' => $fundProvider->organization,
        ]));

        FundProviderAppliedNotification::send($eventLog);
    }

    /**
     * @param FundProviderChatMessageEvent $event
     * @noinspection PhpUnused
     */
    public function onFundProviderChatMessage(FundProviderChatMessageEvent $event): void
    {
        $fund = $event->getFund();

        $eventLog = $fund->log($fund::EVENT_PROVIDER_REPLIED, $this->getFundLogModels($fund, [
            'product' => $event->getChat()->product,
            'provider' => $event->getChat()->fund_provider->organization,
        ]));

        FundProviderChatMessageNotification::send($eventLog);
    }

    /**
     * @param FundProviderInvitedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundProviderInvited(FundProviderInvitedEvent $event): void
    {
        $providerInvitation = $event->getFundProviderInvitation();
        $fundFrom = $providerInvitation->from_fund;
        $providerEmail = $providerInvitation->organization->identity->email;

        if ($providerEmail) {
            $mailable = new ProviderInvitationMail([
                'provider_name'     => $providerInvitation->organization->name,
                'sponsor_name'      => $providerInvitation->fund->organization->name,
                'sponsor_phone'     => $providerInvitation->fund->organization->phone,
                'sponsor_email'     => $providerInvitation->fund->organization->email,
                'fund_name'         => $providerInvitation->fund->name,
                'fund_start_date'   => format_date_locale($providerInvitation->fund->start_date),
                'fund_end_date'     => format_date_locale($providerInvitation->fund->end_date),
                'from_fund_name'    => $fundFrom->name,
                'invitation_link'   => $fundFrom->urlProviderDashboard("/provider-invitations/$providerInvitation->token"),
            ], $providerInvitation->fund->getEmailFrom());

            $this->notificationService->sendSystemMail($providerEmail, $mailable);
        }
    }

    /**
     * @param FundBalanceLowEvent $event
     * @noinspection PhpUnused
     */
    public function onFundBalanceLow(FundBalanceLowEvent $event): void {
        $fund = $event->getFund();
        $implementation = $fund->getImplementation();

        $eventLog = $fund->log($fund::EVENT_BALANCE_LOW, $this->getFundLogModels($fund), [
            'fund_budget_left' => currency_format($fund->budget_left),
            'fund_budget_left_locale' => currency_format_locale($fund->budget_left, $implementation),
            'fund_notification_amount' => currency_format($fund->notification_amount),
            'fund_notification_amount_locale' => currency_format_locale($fund->notification_amount, $implementation),
            'fund_transaction_costs' => currency_format($fund->getTransactionCosts()),
            'fund_transaction_costs_locale' => currency_format_locale($fund->getTransactionCosts(), $implementation),
        ]);

        BalanceLowNotification::send($eventLog);

        if ($fund->organization->low_balance_email) {
            resolve('forus.services.notification')->sendSystemMail(
                $fund->organization->low_balance_email,
                new FundBalanceWarningMail($eventLog->data, $fund->getEmailFrom()),
            );
        }
    }

    /**
     * @param FundBalanceSuppliedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundBalanceSupplied(FundBalanceSuppliedEvent $event): void {
        $fund = $event->getFund();
        $transaction = $event->getTransaction();
        $implementation = $fund->getImplementation();

        $eventLog = $fund->log($fund::EVENT_BALANCE_SUPPLIED, $this->getFundLogModels($fund, [
            'fund_top_up_transaction' => $transaction,
        ]), [
            'fund_top_up_amount' => currency_format($transaction->amount),
            'fund_top_up_amount_locale' => currency_format_locale($transaction->amount, $implementation)
        ]);

        BalanceSuppliedNotification::send($eventLog);
    }

    /**
     * @param FundProductAddedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundProductAdded(FundProductAddedEvent $event): void {
        $fund = $event->getFund();
        $product = $event->getProduct();

        $eventLog = $fund->log($fund::EVENT_PRODUCT_ADDED, $this->getFundLogModels($fund, [
            'product' => $product,
            'provider' => $product->organization,
        ]));

        FundProductAddedNotification::send($eventLog);
        IdentityRequesterProductAddedNotification::send($eventLog);
    }

    /**
     * @param FundProductApprovedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundProductApproved(FundProductApprovedEvent $event): void {
        $fund = $event->getFund();
        $product = $event->getProduct();

        $eventLog = $fund->log($fund::EVENT_PRODUCT_APPROVED, $this->getFundLogModels($fund, [
            'product' => $product,
            'provider' => $product->organization,
        ]));

        IdentityRequesterProductApprovedNotification::send($eventLog);
    }

    /**
     * @param FundProductRevokedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundProductRevoked(FundProductRevokedEvent $event): void
    {
        $fund = $event->getFund();
        $product = $event->getProduct();

        $eventLog = $fund->log($fund::EVENT_PRODUCT_REVOKED, $this->getFundLogModels($fund, [
            'product' => $product,
            'provider' => $product->organization,
        ]));

        IdentityRequesterProductRevokedNotification::send($eventLog);
    }

    /**
     * @param FundArchivedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundArchived(FundArchivedEvent $event): void
    {
        $fund = $event->getFund();

        $eventLog = $fund->log($fund::EVENT_ARCHIVED, $this->getFundLogModels($fund, [
            'employee' => $event->getEmployee(),
        ]));

        FundArchivedNotification::send($eventLog);
    }

    /**
     * @param FundUnArchivedEvent  $event
     * @noinspection PhpUnused
     */
    public function onFundUnArchived(FundUnArchivedEvent $event): void
    {
        $fund = $event->getFund();

        $eventLog = $fund->log($fund::EVENT_UNARCHIVED, $this->getFundLogModels($fund, [
            'employee' => $event->getEmployee(),
        ]));

        FundUnArchivedNotification::send($eventLog);
    }

    /**
     * @param FundVouchersExportedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundVouchersExported(FundVouchersExportedEvent $event): void
    {
        $fund = $event->getFund();
        $details = $event->getExportDetails();

        $fund->log($fund::EVENT_VOUCHERS_EXPORTED, $this->getFundLogModels($fund), array_combine(
            array_map(fn ($key) => "fund_export_$key", array_keys($details)),
            array_values($details)
        ));
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(FundCreatedEvent::class,"$class@onFundCreated");
        $events->listen(FundUpdatedEvent::class,"$class@onFundUpdated");
        $events->listen(FundEndedEvent::class, "$class@onFundEnded");
        $events->listen(FundStartedEvent::class, "$class@onFundStarted");
        $events->listen(FundExpiringEvent::class, "$class@onFundExpiring");
        $events->listen(FundBalanceLowEvent::class, "$class@onFundBalanceLow");
        $events->listen(FundProviderApplied::class, "$class@onFundProviderApplied");
        $events->listen(FundBalanceSuppliedEvent::class, "$class@onFundBalanceSupplied");
        $events->listen(FundProviderInvitedEvent::class, "$class@onFundProviderInvited");
        $events->listen(FundProviderChatMessageEvent::class, "$class@onFundProviderChatMessage");

        $events->listen(FundProductAddedEvent::class, "$class@onFundProductAdded");
        $events->listen(FundProductApprovedEvent::class, "$class@onFundProductApproved");
        $events->listen(FundProductRevokedEvent::class, "$class@onFundProductRevoked");

        $events->listen(FundArchivedEvent::class, "$class@onFundArchived");
        $events->listen(FundUnArchivedEvent::class, "$class@onFundUnArchived");

        $events->listen(FundVouchersExportedEvent::class, "$class@onFundVouchersExported");
    }
}
