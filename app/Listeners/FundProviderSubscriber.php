<?php

namespace App\Listeners;

use App\Events\FundProviders\FundProviderApprovedBudget;
use App\Events\FundProviders\FundProviderApprovedProducts;
use App\Events\FundProviders\FundProviderRevokedBudget;
use App\Events\FundProviders\FundProviderRevokedProducts;
use App\Events\FundProviders\FundProviderSponsorChatMessage;
use App\Events\FundProviders\FundProviderStateUpdated;
use App\Models\FundProvider;
use App\Notifications\Organizations\FundProviders\FundProvidersApprovedBudgetNotification;
use App\Notifications\Organizations\FundProviders\FundProvidersApprovedProductsNotification;
use App\Notifications\Organizations\FundProviders\FundProviderSponsorChatMessageNotification;
use App\Notifications\Organizations\FundProviders\FundProvidersRevokedBudgetNotification;
use App\Notifications\Organizations\FundProviders\FundProvidersRevokedProductsNotification;
use App\Notifications\Identities\Fund\IdentityRequesterProviderApprovedBudgetNotification;
use App\Notifications\Identities\Fund\IdentityRequesterProviderApprovedProductsNotification;
use App\Notifications\Organizations\FundProviders\FundProvidersStateAcceptedNotification;
use App\Notifications\Organizations\FundProviders\FundProvidersStateRejectedNotification;
use Illuminate\Events\Dispatcher;

/**
 * Class FundSubscriber
 * @package App\Listeners
 */
class FundProviderSubscriber
{
    /**
     * @param FundProvider $fundProvider
     * @return array
     */
    private function getFundProviderLogModels(FundProvider $fundProvider): array
    {
        return [
            'implementation' => $fundProvider->fund->getImplementation(),
            'provider' => $fundProvider->organization,
            'sponsor' => $fundProvider->fund->organization,
            'fund' => $fundProvider->fund,
        ];
    }

    /**
     * @param FundProviderStateUpdated $event
     * @noinspection PhpUnused
     */
    public function onStateUpdated(FundProviderStateUpdated $event): void
    {
        $fundProvider = $event->getFundProvider();
        $models = $this->getFundProviderLogModels($fundProvider);
        $originalState = $event->getOriginalState();

        $raw = [
            'fund_provider_approved_before' => $event->getApprovedBefore(),
            'fund_provider_approved_after' => $event->getApprovedAfter(),
            'fund_provider_original_state' => $originalState,
        ];

        if ($fundProvider->isAccepted()) {
            $eventLog = $fundProvider->log($fundProvider::EVENT_STATE_ACCEPTED, $models, $raw);

            if ($event->getApprovedAfter() || ($originalState == $fundProvider::STATE_PENDING)) {
                FundProvidersStateAcceptedNotification::send($eventLog);
            }
        }

        if ($fundProvider->isRejected()) {
            $eventLog = $fundProvider->log($fundProvider::EVENT_STATE_REJECTED, $models, $raw);

            if ($event->getApprovedBefore() || ($originalState == $fundProvider::STATE_PENDING)) {
                FundProvidersStateRejectedNotification::send($eventLog);
            }
        }
    }

    /**
     * @param FundProviderApprovedBudget $event
     * @noinspection PhpUnused
     */
    public function onApprovedBudget(FundProviderApprovedBudget $event): void
    {
        $fundProvider = $event->getFundProvider();

        FundProvidersApprovedBudgetNotification::send($fundProvider->log(
            $fundProvider::EVENT_APPROVED_BUDGET,
            $this->getFundProviderLogModels($fundProvider)
        ));

        IdentityRequesterProviderApprovedBudgetNotification::send($fundProvider->fund->log(
            $fundProvider->fund::EVENT_PROVIDER_APPROVED_BUDGET,
            $this->getFundProviderLogModels($fundProvider)
        ));
    }

    /**
     * @param FundProviderApprovedProducts $event
     * @noinspection PhpUnused
     */
    public function onApprovedProducts(FundProviderApprovedProducts $event): void
    {
        $fundProvider = $event->getFundProvider();

        FundProvidersApprovedProductsNotification::send($fundProvider->log(
            $fundProvider::EVENT_APPROVED_PRODUCTS,
            $this->getFundProviderLogModels($fundProvider)
        ));

        IdentityRequesterProviderApprovedProductsNotification::send($fundProvider->fund->log(
            $fundProvider->fund::EVENT_PROVIDER_APPROVED_PRODUCTS,
            $this->getFundProviderLogModels($fundProvider)
        ));
    }

    /**
     * @param FundProviderRevokedBudget $event
     * @noinspection PhpUnused
     */
    public function onRevokedBudget(FundProviderRevokedBudget $event): void
    {
        $fundProvider = $event->getFundProvider();

        $fundProvider->fund->log(
            $fundProvider->fund::EVENT_PROVIDER_REVOKED_BUDGET,
            $this->getFundProviderLogModels($fundProvider)
        );

        FundProvidersRevokedBudgetNotification::send($fundProvider->log(
            $fundProvider::EVENT_REVOKED_BUDGET,
            $this->getFundProviderLogModels($fundProvider)
        ));
    }

    /**
     * @param FundProviderRevokedProducts $event
     * @noinspection PhpUnused
     */
    public function onRevokedProducts(FundProviderRevokedProducts $event): void
    {
        $fundProvider = $event->getFundProvider();

        $fundProvider->fund->log(
            $fundProvider->fund::EVENT_PROVIDER_REVOKED_PRODUCTS,
            $this->getFundProviderLogModels($fundProvider)
        );

        FundProvidersRevokedProductsNotification::send($fundProvider->log(
            $fundProvider::EVENT_REVOKED_PRODUCTS,
            $this->getFundProviderLogModels($fundProvider)
        ));
    }

    /**
     * @param FundProviderSponsorChatMessage $event
     * @noinspection PhpUnused
     */
    public function onSponsorMessage(FundProviderSponsorChatMessage $event): void
    {
        $chat = $event->getChat();
        $fundProvider = $chat->fund_provider;

        $eventLog = $fundProvider->log($fundProvider::EVENT_SPONSOR_MESSAGE, array_merge([
            'product' => $chat->product,
        ], $this->getFundProviderLogModels($fundProvider)));

        FundProviderSponsorChatMessageNotification::send($eventLog);
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

        $events->listen(FundProviderStateUpdated::class, "$class@onStateUpdated");
        $events->listen(FundProviderApprovedBudget::class, "$class@onApprovedBudget");
        $events->listen(FundProviderApprovedProducts::class, "$class@onApprovedProducts");
        $events->listen(FundProviderRevokedBudget::class, "$class@onRevokedBudget");
        $events->listen(FundProviderRevokedProducts::class, "$class@onRevokedProducts");
        $events->listen(FundProviderSponsorChatMessage::class, "$class@onSponsorMessage");
    }
}
