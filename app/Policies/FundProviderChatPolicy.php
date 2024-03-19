<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderChat;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FundProviderChatPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can create fund provider chats.
     *
     * @param Identity $identity
     * @param FundProvider $fundProvider
     * @param Fund $fund
     * @param Organization $organization
     * @return \Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function createSponsor(
        Identity $identity,
        FundProvider $fundProvider,
        Fund $fund,
        Organization $organization
    ): Response {
        $integrityValidation = $this->checkIntegritySponsor(
            $organization,
            $fund,
            $fundProvider
        );

        if ($integrityValidation !== true) {
            return $this->deny('integrity');
        }

        if (!$organization->identityCan($identity, 'manage_providers')) {
            return $this->deny();
        }

        return $this->allow();
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param FundProviderChat|null $fundProviderChat
     * @return bool
     */
    private function checkIntegritySponsor(
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider,
        ?FundProviderChat $fundProviderChat = null
    ): bool {
        if ($organization->id != $fund->organization_id) {
            return false;
        }

        if ($fund->id != $fundProvider->fund_id) {
            return false;
        }

        if (!is_null($fundProviderChat) &&
            $fundProviderChat->fund_provider_id != $fundProvider->id) {
            return false;
        }

        return true;
    }

    /**
     * @param Organization $organization
     * @param Product $product
     * @param FundProviderChat|null $fundProviderChat
     * @return bool
     */
    private function checkIntegrityProvider(
        Organization $organization,
        Product $product,
        ?FundProviderChat $fundProviderChat = null
    ): bool {
        if ($organization->id != $product->organization_id) {
            return false;
        }

        if (!is_null($fundProviderChat) &&
            $fundProviderChat->product_id != $product->id) {
            return false;
        }

        return true;
    }
}
