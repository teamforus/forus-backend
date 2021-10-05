<?php

namespace App\Listeners;

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
use App\Events\Funds\FundUpdatedEvent;
use App\Mail\Forus\ForusFundCreatedMail;
use App\Mail\Funds\ProviderInvitationMail;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Notifications\Identities\Fund\IdentityRequesterFundEndedNotification;
use App\Notifications\Identities\Fund\IdentityRequesterProductRevokedNotification;
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
    private $recordRepo;

    /**
     * FundSubscriber constructor.
     */
    public function __construct()
    {
        $this->recordRepo = resolve('forus.services.record');
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

        FundCreatedNotification::send($fund->log(Fund::EVENT_CREATED, [
            'fund' => $fund,
            'sponsor' => $fund->organization,
        ]));

        if ($email = env('EMAIL_FOR_FUND_CREATED', false)) {
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

        FundStartedNotification::send($fund->log(Fund::EVENT_FUND_STARTED, [
            'fund' => $fund,
            'organization' => $fund->organization,
        ]));

        /** @var FundProvider[] $fundProviders */
        $fundProviders = FundProviderQuery::whereApprovedForFundsFilter(
            $fund->providers()->getQuery(), $fund->id
        )->get();

        foreach ($fundProviders as $fundProvider) {
            FundProviderFundStartedNotification::send(
                $fundProvider->log(FundProvider::EVENT_FUND_STARTED, [
                    'fund' => $fund,
                    'sponsor' => $fund->organization,
                    'provider' => $fundProvider->organization,
                ])
            );
        }
    }

    /**
     * @param FundExpiringEvent $event
     * @noinspection PhpUnused
     */
    public function onFundExpiring(FundExpiringEvent $event): void {
        $fund = $event->getFund();

        FundExpiringNotification::send($fund->log(Fund::EVENT_FUND_EXPIRING, [
            'fund' => $fund,
            'sponsor' => $fund->organization,
        ]));

        /** @var FundProvider[] $fundProviders */
        $fundProviders = FundProviderQuery::whereApprovedForFundsFilter(
            $fund->providers()->getQuery(), $fund->id
        )->get();

        foreach ($fundProviders as $fundProvider) {
            FundProviderFundExpiringNotification::send(
                $fundProvider->log(FundProvider::EVENT_FUND_EXPIRING, [
                    'fund' => $fund,
                    'sponsor' => $fund->organization,
                    'provider' => $fundProvider->organization,
                ])
            );
        }
    }

    /**
     * @param FundEndedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundEnded(FundEndedEvent $event): void
    {
        $fund = $event->getFund();
        $eventLog = $fund->log(Fund::EVENT_FUND_ENDED, [
            'fund' => $fund,
            'sponsor' => $fund->organization,
        ]);

        FundEndedNotification::send($eventLog);
        IdentityRequesterFundEndedNotification::send($eventLog);

        $query = $fund->providers()->getQuery();
        $fundProviders = FundProviderQuery::whereApprovedForFundsFilter($query, $fund->id)->get();

        /** @var FundProvider[] $fundProviders */
        foreach ($fundProviders as $fundProvider) {
            $eventLog = $fundProvider->log(FundProvider::EVENT_FUND_ENDED, [
                'fund' => $fund,
                'sponsor' => $fund->organization,
                'provider' => $fundProvider->organization,
            ]);

            FundProviderFundEndedNotification::send($eventLog);
        }
    }

    /**
     * @param FundProviderApplied $event
     * @noinspection PhpUnused
     */
    public function onFundProviderApplied(FundProviderApplied $event): void
    {
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
    public function onFundProviderChatMessage(FundProviderChatMessageEvent $event): void
    {
        FundProviderChatMessageNotification::send(
            $event->getFund()->log(Fund::EVENT_PROVIDER_REPLIED, [
                'fund' => $event->getFund(),
                'product' => $event->getChat()->product,
                'sponsor' => $event->getFund()->organization,
                'provider' => $event->getChat()->fund_provider->organization,
            ])
        );
    }

    /**
     * @param FundProviderInvitedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundProviderInvited(FundProviderInvitedEvent $event): void
    {
        $providerInvitation = $event->getFundProviderInvitation();
        $fundFrom = $providerInvitation->from_fund;
        $providerEmail = $this->recordRepo->primaryEmailByAddress($providerInvitation->organization->identity_address);

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
            'sponsor' => $fund->organization,
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
                'sponsor' => $fund->organization,
                'provider' => $product->organization,
            ])
        );
    }

    /**
     * @param FundProductRevokedEvent $event
     * @noinspection PhpUnused
     */
    public function onFundProductRevoked(FundProductRevokedEvent $event): void
    {
        $fund = $event->getFund();
        $product = $event->getProduct();

        $event = $fund->log(Fund::EVENT_PRODUCT_REVOKED, [
            'fund' => $fund,
            'product' => $product,
            'sponsor' => $fund->organization,
            'provider' => $product->organization,
        ]);

        IdentityRequesterProductRevokedNotification::send($event);
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(FundCreatedEvent::class,'\App\Listeners\FundSubscriber@onFundCreated');
        $events->listen(FundUpdatedEvent::class,'\App\Listeners\FundSubscriber@onFundUpdated');
        $events->listen(FundEndedEvent::class, '\App\Listeners\FundSubscriber@onFundEnded');
        $events->listen(FundStartedEvent::class, '\App\Listeners\FundSubscriber@onFundStarted');
        $events->listen(FundExpiringEvent::class, '\App\Listeners\FundSubscriber@onFundExpiring');
        $events->listen(FundBalanceLowEvent::class, '\App\Listeners\FundSubscriber@onFundBalanceLow');
        $events->listen(FundProviderApplied::class, '\App\Listeners\FundSubscriber@onFundProviderApplied');
        $events->listen(FundBalanceSuppliedEvent::class, '\App\Listeners\FundSubscriber@onFundBalanceSupplied');
        $events->listen(FundProviderInvitedEvent::class, '\App\Listeners\FundSubscriber@onFundProviderInvited');
        $events->listen(FundProviderChatMessageEvent::class, '\App\Listeners\FundSubscriber@onFundProviderChatMessage');

        $events->listen(FundProductAddedEvent::class, '\App\Listeners\FundSubscriber@onFundProductAdded');
        $events->listen(FundProductApprovedEvent::class, '\App\Listeners\FundSubscriber@onFundProductApproved');
        $events->listen(FundProductRevokedEvent::class, '\App\Listeners\FundSubscriber@onFundProductRevoked');
    }
}
