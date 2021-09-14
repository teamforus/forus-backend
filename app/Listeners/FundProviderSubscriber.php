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
    /**
     * @param FundProviderApprovedBudget $event
     */
    public function onApprovedBudget(FundProviderApprovedBudget $event): void {
        $fundProvider = $event->getFundProvider();

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
        $eventLog = $fundProvider->log(FundProvider::EVENT_REVOKED_BUDGET, [
            'provider' => $fundProvider->organization,
            'sponsor' => $fundProvider->fund->organization,
            'fund' => $fundProvider->fund,
        ]);

        $fundProvider->fund->log(Fund::EVENT_PROVIDER_REVOKED_BUDGET, [
            'provider' => $fundProvider->organization,
            'sponsor' => $fundProvider->fund->organization,
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
            'sponsor' => $fundProvider->fund->organization,
            'fund' => $fundProvider->fund,
        ]);

        $fundProvider->fund->log(Fund::EVENT_PROVIDER_REVOKED_PRODUCTS, [
            'provider' => $fundProvider->organization,
            'sponsor' => $fundProvider->fund->organization,
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
            'provider' => $fundProvider->organization,
            'sponsor' => $fundProvider->fund->organization,
            'product' => $chat->product,
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
