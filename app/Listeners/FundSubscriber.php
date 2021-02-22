<?php

namespace App\Listeners;

use App\Events\Funds\FundBalanceLowEvent;
use App\Events\Funds\FundBalanceSuppliedEvent;
use App\Events\Funds\FundEndedEvent;
use App\Events\Funds\FundCreated;
use App\Events\Funds\FundExpiringEvent;
use App\Events\Funds\FundProductAddedEvent;
use App\Events\Funds\FundProductApprovedEvent;
use App\Events\Funds\FundProductRevokedEvent;
use App\Events\Funds\FundProviderApplied;
use App\Events\Funds\FundProviderChatMessage;
use App\Events\Funds\FundProviderChatMessageEvent;
use App\Events\Funds\FundStartedEvent;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Notifications\Organizations\FundProviders\FundProviderFundEndedNotification;
use App\Notifications\Organizations\FundProviders\FundProviderFundExpiringNotification;
use App\Notifications\Organizations\FundProviders\FundProviderFundStartedNotification;
use App\Notifications\Organizations\Funds\BalanceLowNotification;
use App\Notifications\Organizations\Funds\BalanceSuppliedNotification;
use App\Notifications\Organizations\Funds\FundCreatedNotification;
use App\Notifications\Organizations\Funds\FundEndedNotification;
use App\Notifications\Organizations\Funds\FundExpiringNotification;
use App\Notifications\Organizations\Funds\FundProductAddedNotification;
use App\Notifications\Organizations\Funds\FundProviderAppliedNotification;
use App\Notifications\Organizations\Funds\FundProviderChatMessageNotification;
use App\Notifications\Organizations\Funds\FundStartedNotification;
use App\Notifications\Identities\Fund\IdentityRequesterProductAddedNotification;
use App\Notifications\Identities\Fund\IdentityRequesterProductApprovedNotification;
use App\Scopes\Builders\FundProviderQuery;
use Illuminate\Events\Dispatcher;

/**
 * Class FundSubscriber
 * @package App\Listeners
 */
class FundSubscriber
{
    private $notificationService;

    /**
     * FundSubscriber constructor.
     */
    public function __construct()
    {
        $this->notificationService = resolve('forus.services.notification');
    }

    /**
     * @param FundCreated $event
     * @noinspection PhpUnused
     */
    public function onFundCreated(FundCreated $event): void {
        $fund = $event->getFund();

        FundCreatedNotification::send($fund->log(Fund::EVENT_CREATED, [
            'fund' => $fund,
            'sponsor' => $fund->organization,
        ]));

        if ($email = env('EMAIL_FOR_FUND_CREATED', false)) {
            $this->notificationService->newFundCreatedNotifyForus(
                $email,
                Implementation::emailFrom(),
                $fund->name,
                $fund->organization->name
            );
        }
    }

    /**
     * @param FundStartedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundStarted(FundStartedEvent $event): void {
        $fund = $event->getFund();

        FundStartedNotification::send($fund->log(Fund::EVENT_FUND_STARTED, compact('fund')));

        $fundProviders = FundProviderQuery::whereApprovedForFundsFilter(
            $fund->providers()->getQuery(), $fund->id
        )->get();

        foreach ($fundProviders as $fundProvider) {
            FundProviderFundStartedNotification::send(
                $fundProvider->log(FundProvider::EVENT_FUND_STARTED, compact('fund'))
            );
        }
    }

    /**
     * @param FundExpiringEvent $event
     * @noinspection PhpUnused
     */
    public function onFundExpiring(FundExpiringEvent $event): void {
        $fund = $event->getFund();

        FundExpiringNotification::send($fund->log(Fund::EVENT_FUND_EXPIRING, compact('fund')));

        $fundProviders = FundProviderQuery::whereApprovedForFundsFilter(
            $fund->providers()->getQuery(), $fund->id
        )->get();

        foreach ($fundProviders as $fundProvider) {
            FundProviderFundExpiringNotification::send(
                $fundProvider->log(FundProvider::EVENT_FUND_EXPIRING, compact('fund'))
            );
        }
    }

    /**
     * @param FundEndedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundEnded(FundEndedEvent $event): void {
        $fund = $event->getFund();

        FundEndedNotification::send($fund->log(Fund::EVENT_FUND_ENDED, compact('fund')));

        $fundProviders = FundProviderQuery::whereApprovedForFundsFilter(
            $fund->providers()->getQuery(), $fund->id
        )->get();

        foreach ($fundProviders as $fundProvider) {
            FundProviderFundEndedNotification::send(
                $fundProvider->log(FundProvider::EVENT_FUND_ENDED, compact('fund'))
            );
        }

        foreach ($fund->provider_organizations_approved as $organization) {
            $this->notificationService->fundClosedProvider(
                $organization->email,
                $fund->fund_config->implementation->getEmailFrom(),
                $fund->name,
                $fund->end_date,
                $organization->name,
                $fund->fund_config->implementation->url_provider ?? env('PANEL_PROVIDER_URL')
            );
        }

        $identities = $fund->vouchers()->whereNotNull(
            'identity_address'
        )->pluck('identity_address')->unique();

        $emails = $identities->map(static function($identity_address) {
            return record_repo()->primaryEmailByAddress($identity_address);
        });

        foreach ($emails as $email) {
            $this->notificationService->fundClosed(
                $email,
                $fund->fund_config->implementation->getEmailFrom(),
                $fund->name,
                $fund->end_date,
                $fund->organization->email,
                $fund->organization->name,
                $fund->fund_config->implementation->url_webshop
            );
        }
    }

    /**
     * @param FundProviderApplied $event
     * @noinspection PhpUnused
     */
    public function onFundProviderApplied(FundProviderApplied $event): void {
        $fundProvider = $event->getFundProvider();

        FundProviderAppliedNotification::send(
            $fundProvider->fund->log(Fund::EVENT_PROVIDER_APPLIED, [
                'fund' => $fundProvider->fund,
                'sponsor' => $fundProvider->fund->organization,
                'provider' => $fundProvider->organization,
            ])
        );
    }

    /**
     * @param FundProviderChatMessageEvent $event
     * @noinspection PhpUnused
     */
    public function onFundProviderChatMessage(FundProviderChatMessageEvent $event): void {
        FundProviderChatMessageNotification::send(
            $event->getFund()->log(Fund::EVENT_PROVIDER_REPLIED, [
                'fund' => $event->getFund(),
                'product' => $event->getChat()->product,
                'provider' => $event->getChat()->fund_provider->organization,
            ])
        );
    }

    /**
     * @param FundBalanceLowEvent $event
     * @noinspection PhpUnused
     */
    public function onFundBalanceLow(FundBalanceLowEvent $event): void {
        $fund = $event->getFund();

        BalanceLowNotification::send($fund->log(Fund::EVENT_BALANCE_LOW, [
            'fund' => $fund,
            'sponsor' => $fund->organization,
        ], [
            'fund_budget_left' => currency_format($fund->budget_left),
            'fund_budget_left_locale' => currency_format_locale($fund->budget_left),
            'fund_notification_amount' => currency_format($fund->notification_amount),
            'fund_notification_amount_locale' => currency_format_locale($fund->notification_amount),
        ]));
    }

    /**
     * @param FundBalanceSuppliedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundBalanceSupplied(FundBalanceSuppliedEvent $event): void {
        $fund = $event->getFund();
        $transaction = $event->getTransaction();

        BalanceSuppliedNotification::send($fund->log(Fund::EVENT_BALANCE_SUPPLIED, [
            'fund' => $fund,
            'fund_top_up_transaction' => $transaction,
        ], [
            'fund_top_up_amount' => currency_format($transaction->amount),
            'fund_top_up_amount_locale' => currency_format_locale($transaction->amount)
        ]));
    }

    /**
     * @param FundProductAddedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundProductAdded(FundProductAddedEvent $event): void {
        $fund = $event->getFund();
        $product = $event->getProduct();

        $eventLog = $fund->log(Fund::EVENT_PRODUCT_ADDED, [
            'fund' => $fund,
            'product' => $product,
            'provider' => $product->organization,
        ]);

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

        IdentityRequesterProductApprovedNotification::send(
            $fund->log(Fund::EVENT_PRODUCT_APPROVED, [
                'fund' => $fund,
                'product' => $product,
                'provider' => $product->organization,
            ])
        );
    }

    /**
     * @param FundProductRevokedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundProductRevoked(FundProductRevokedEvent $event): void {
        $fund = $event->getFund();
        $product = $event->getProduct();

        IdentityRequesterProductApprovedNotification::send(
            $fund->log(Fund::EVENT_PRODUCT_REVOKED, [
                'fund' => $fund,
                'product' => $product,
                'provider' => $product->organization,
            ])
        );
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(FundCreated::class,'\App\Listeners\FundSubscriber@onFundCreated');
        $events->listen(FundEndedEvent::class, '\App\Listeners\FundSubscriber@onFundEnded');
        $events->listen(FundStartedEvent::class, '\App\Listeners\FundSubscriber@onFundStarted');
        $events->listen(FundExpiringEvent::class, '\App\Listeners\FundSubscriber@onFundExpiring');
        $events->listen(FundBalanceLowEvent::class, '\App\Listeners\FundSubscriber@onFundBalanceLow');
        $events->listen(FundProviderApplied::class, '\App\Listeners\FundSubscriber@onFundProviderApplied');
        $events->listen(FundBalanceSuppliedEvent::class, '\App\Listeners\FundSubscriber@onFundBalanceSupplied');
        $events->listen(FundProviderChatMessageEvent::class, '\App\Listeners\FundSubscriber@onFundProviderChatMessage');

        $events->listen(FundProductAddedEvent::class, '\App\Listeners\FundSubscriber@onFundProductAdded');
        $events->listen(FundProductApprovedEvent::class, '\App\Listeners\FundSubscriber@onFundProductApproved');
        $events->listen(FundProductRevokedEvent::class, '\App\Listeners\FundSubscriber@onFundProductRevoked');
    }
}
