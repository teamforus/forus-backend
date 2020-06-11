<?php

namespace App\Listeners;

use App\Events\FundProviders\FundProviderApplied;
use App\Events\FundProviders\FundProviderApprovedBudget;
use App\Events\FundProviders\FundProviderApprovedProducts;
use App\Events\FundProviders\FundProviderReplied;
use App\Events\FundProviders\FundProviderRevokedBudget;
use App\Events\FundProviders\FundProviderRevokedProducts;
use App\Events\FundProviders\FundProviderSponsorChatMessage;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Notifications\Organizations\FundProviders\FundProvidersApprovedBudgetNotification;
use App\Notifications\Organizations\FundProviders\FundProvidersApprovedProductsNotification;
use App\Notifications\Organizations\FundProviders\FundProviderSponsorChatMessageNotification;
use App\Notifications\Organizations\FundProviders\FundProvidersRevokedBudgetNotification;
use App\Notifications\Organizations\FundProviders\FundProvidersRevokedProductsNotification;
use App\Notifications\Identities\Fund\IdentityRequesterProviderApprovedBudgetNotification;
use App\Notifications\Identities\Fund\IdentityRequesterProviderApprovedProductsNotification;
use Illuminate\Events\Dispatcher;

/**
 * Class FundSubscriber
 * @package App\Listeners
 */
class FundProviderSubscriber
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
     * @param FundProviderApprovedBudget $event
     */
    public function onApprovedBudget(FundProviderApprovedBudget $event): void {
        $fundProvider = $event->getFundProvider();
        $implementation = $fundProvider->fund->fund_config->implementation;

        $this->notificationService->providerApproved(
            $fundProvider->organization->email,
            $implementation->getEmailFrom(),
            $fundProvider->fund->name,
            $fundProvider->organization->name,
            $fundProvider->fund->organization->name,
            $fundProvider->fund->urlProviderDashboard()
        );

        $transData =  [
            "fund_name" => $fundProvider->fund->name,
            "sponsor_phone" => $fundProvider->organization->phone
        ];

        $this->notificationService->sendPushNotification(
            $fundProvider->organization->identity_address,
            trans('push.providers.accepted.title', $transData),
            trans('push.providers.accepted.body', $transData),
            'funds.provider_approved'
        );

        $providerEventLog = $fundProvider->log(FundProvider::EVENT_APPROVED_BUDGET, [
            'provider' => $fundProvider->organization,
            'sponsor' => $fundProvider->fund->organization,
            'fund' => $fundProvider->fund,
        ]);

        $fundEventLog = $fundProvider->fund->log(Fund::EVENT_PROVIDER_APPROVED_BUDGET, [
            'provider' => $fundProvider->organization,
            'sponsor' => $fundProvider->fund->organization,
            'fund' => $fundProvider->fund,
        ]);

        FundProvidersApprovedBudgetNotification::send($providerEventLog);
        IdentityRequesterProviderApprovedBudgetNotification::send($fundEventLog);
    }

    /**
     * @param FundProviderApprovedProducts $event
     */
    public function onApprovedProducts(FundProviderApprovedProducts $event): void {
        $fundProvider = $event->getFundProvider();

        $providerEventLog = $fundProvider->log(FundProvider::EVENT_APPROVED_PRODUCTS, [
            'provider' => $fundProvider->organization,
            'sponsor' => $fundProvider->fund->organization,
            'fund' => $fundProvider->fund,
        ]);

        $fundEventLog = $fundProvider->fund->log(Fund::EVENT_PROVIDER_APPROVED_PRODUCTS, [
            'provider' => $fundProvider->organization,
            'sponsor' => $fundProvider->fund->organization,
            'fund' => $fundProvider->fund,
        ]);

        FundProvidersApprovedProductsNotification::send($providerEventLog);
        IdentityRequesterProviderApprovedProductsNotification::send($fundEventLog);
    }

    /**
     * @param FundProviderRevokedBudget $event
     */
    public function onRevokedBudget(FundProviderRevokedBudget $event): void {
        $fundProvider = $event->getFundProvider();
        $implementation = $fundProvider->fund->fund_config->implementation;

        $this->notificationService->providerRejected(
            $fundProvider->organization->email,
            $implementation->getEmailFrom(),
            $fundProvider->fund->name,
            $fundProvider->organization->name,
            $fundProvider->fund->organization->name,
            $fundProvider->fund->organization->phone
        );

        $eventLog = $fundProvider->log(FundProvider::EVENT_REVOKED_BUDGET, [
            'provider' => $fundProvider->organization,
            'fund' => $fundProvider->fund,
        ]);

        $fundProvider->fund->log(Fund::EVENT_PROVIDER_REVOKED_BUDGET, [
            'provider' => $fundProvider->organization,
            'fund' => $fundProvider->fund,
        ]);

        FundProvidersRevokedBudgetNotification::send($eventLog);
    }

    /**
     * @param FundProviderRevokedProducts $event
     */
    public function onRevokedProducts(FundProviderRevokedProducts $event): void {
        $fundProvider = $event->getFundProvider();

        $eventLog = $fundProvider->log(FundProvider::EVENT_REVOKED_PRODUCTS, [
            'provider' => $fundProvider->organization,
            'fund' => $fundProvider->fund,
        ]);

        $fundProvider->fund->log(Fund::EVENT_PROVIDER_REVOKED_PRODUCTS, [
            'provider' => $fundProvider->organization,
            'fund' => $fundProvider->fund,
        ]);

        FundProvidersRevokedProductsNotification::send($eventLog);
    }

    /**
     * @param FundProviderSponsorChatMessage $event
     */
    public function onSponsorMessage(FundProviderSponsorChatMessage $event): void {
        $chat = $event->getChat();
        $fundProvider = $chat->fund_provider;


        $eventLog = $fundProvider->log(FundProvider::EVENT_SPONSOR_MESSAGE, [
            'product' => $chat->product,
            'provider' => $fundProvider->organization,
            'sponsor' => $fundProvider->fund->organization,
            'fund' => $fundProvider->fund,
        ]);

        FundProviderSponsorChatMessageNotification::send($eventLog);
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            FundProviderApprovedBudget::class,
            '\App\Listeners\FundProviderSubscriber@onApprovedBudget'
        );

        $events->listen(
            FundProviderApprovedProducts::class,
            '\App\Listeners\FundProviderSubscriber@onApprovedProducts'
        );

        $events->listen(
            FundProviderRevokedBudget::class,
            '\App\Listeners\FundProviderSubscriber@onRevokedBudget'
        );

        $events->listen(
            FundProviderRevokedProducts::class,
            '\App\Listeners\FundProviderSubscriber@onRevokedProducts'
        );

        $events->listen(
            FundProviderSponsorChatMessage::class,
            '\App\Listeners\FundProviderSubscriber@onSponsorMessage'
        );
    }
}
