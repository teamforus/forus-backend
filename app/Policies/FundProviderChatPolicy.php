<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderChat;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundProviderChatPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any fund provider chats.
     *
     * @param string $identity_address
     * @param FundProvider $fundProvider
     * @param Fund $fund
     * @param Organization $organization
     * @return \Illuminate\Auth\Access\Response
     */
    public function viewAnySponsor(
        string $identity_address,
        FundProvider $fundProvider,
        Fund $fund,
        Organization $organization
    ) {
        return $this->createSponsor(
            $identity_address,
            $fundProvider,
            $fund,
            $organization
        );
    }

    /**
     * Determine whether the user can view any fund provider chats.
     *
     * @param string $identity_address
     * @param Product $product
     * @param Organization $organization
     * @return \Illuminate\Auth\Access\Response
     */
    public function viewAnyProvider(
        string $identity_address,
        Product $product,
        Organization $organization
    ) {
        $integrityValidation = $this->checkIntegrityProvider(
            $organization,
            $product
        );

        if ($integrityValidation !== true) {
            return $this->deny('integrity');
        }

        if (!$organization->identityCan($identity_address, 'manage_provider_funds')) {
            return $this->deny();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can view the fund provider chat.
     *
     * @param string $identity_address
     * @param FundProviderChat $fundProviderChat
     * @param FundProvider $fundProvider
     * @param Fund $fund
     * @param Organization $organization
     * @return \Illuminate\Auth\Access\Response
     */
    public function viewSponsor(
        string $identity_address,
        FundProviderChat $fundProviderChat,
        FundProvider $fundProvider,
        Fund $fund,
        Organization $organization
    ) {
        $integrityValidation = $this->checkIntegritySponsor(
            $organization,
            $fund,
            $fundProvider,
            $fundProviderChat
        );

        if ($integrityValidation !== true) {
            return $this->deny('integrity');
        }

        if (!$organization->identityCan($identity_address, 'manage_providers')) {
            return $this->deny();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can view the fund provider chat.
     *
     * @param string $identity_address
     * @param FundProviderChat $fundProviderChat
     * @param Product $product
     * @param Organization $organization
     * @return \Illuminate\Auth\Access\Response
     */
    public function viewProvider(
        string $identity_address,
        FundProviderChat $fundProviderChat,
        Product $product,
        Organization $organization
    ) {
        $integrityValidation = $this->checkIntegrityProvider(
            $organization,
            $product,
            $fundProviderChat
        );

        if ($integrityValidation !== true) {
            return $this->deny('integrity');
        }

        if (!$organization->identityCan($identity_address, 'manage_provider_funds')) {
            return $this->deny();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can create fund provider chats.
     *
     * @param string $identity_address
     * @param FundProvider $fundProvider
     * @param Fund $fund
     * @param Organization $organization
     * @return \Illuminate\Auth\Access\Response
     */
    public function createSponsor(
        string $identity_address,
        FundProvider $fundProvider,
        Fund $fund,
        Organization $organization
    ) {
        $integrityValidation = $this->checkIntegritySponsor(
            $organization,
            $fund,
            $fundProvider
        );

        if ($integrityValidation !== true) {
            return $this->deny('integrity');
        }

        if (!$organization->identityCan($identity_address, 'manage_providers')) {
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
    ) {
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

    private function checkIntegrityProvider(
        Organization $organization,
        Product $product,
        ?FundProviderChat $fundProviderChat = null
    ) {
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
