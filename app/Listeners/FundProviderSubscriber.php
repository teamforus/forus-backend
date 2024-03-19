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
     *
     * @return (\App\Models\Fund|\App\Models\Implementation|\App\Models\Organization)[]
     *
     * @psalm-return array{implementation: \App\Models\Implementation, provider: \App\Models\Organization, sponsor: \App\Models\Organization, fund: \App\Models\Fund}
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
}
