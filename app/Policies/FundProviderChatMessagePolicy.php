<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderChat;
use App\Models\FundProviderChatMessage;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FundProviderChatMessagePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any fund provider chats.
     *
     * @param Identity $identity
     * @param FundProviderChat $fundProviderChat
     * @param FundProvider $fundProvider
     * @param Fund $fund
     * @param Organization $organization
     * @return \Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function viewAnySponsor(
        Identity $identity,
        FundProviderChat $fundProviderChat,
        FundProvider $fundProvider,
        Fund $fund,
        Organization $organization
    ): Response {
        return $this->createSponsor(
            $identity,
            $fundProviderChat,
            $fundProvider,
            $fund,
            $organization
        );
    }

    /**
     * Determine whether the user can view any fund provider chats.
     *
     * @param Identity $identity
     * @param FundProviderChat $fundProviderChat
     * @param Product $product
     * @param Organization $organization
     * @return \Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function viewAnyProvider(
        Identity $identity,
        FundProviderChat $fundProviderChat,
        Product $product,
        Organization $organization
    ): Response {
        return $this->createProvider(
            $identity,
            $fundProviderChat,
            $product,
            $organization
        );
    }

    /**
     * Determine whether the user can view the fund provider chat.
     *
     * @param Identity $identity
     * @param FundProviderChatMessage $fundProviderChatMessage
     * @param FundProviderChat $fundProviderChat
     * @param FundProvider $fundProvider
     * @param Fund $fund
     * @param Organization $organization
     * @return \Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function viewSponsor(
        Identity $identity,
        FundProviderChatMessage $fundProviderChatMessage,
        FundProviderChat $fundProviderChat,
        FundProvider $fundProvider,
        Fund $fund,
        Organization $organization
    ): Response {
        $integrityValidation = $this->checkIntegritySponsor(
            $organization,
            $fund,
            $fundProvider,
            $fundProviderChat,
            $fundProviderChatMessage
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
     * Determine whether the user can view the fund provider chat.
     *
     * @param Identity $identity
     * @param FundProviderChatMessage $fundProviderChatMessage
     * @param FundProviderChat $fundProviderChat
     * @param Product $product
     * @param Organization $organization
     * @return \Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function viewProvider(
        Identity $identity,
        FundProviderChatMessage $fundProviderChatMessage,
        FundProviderChat $fundProviderChat,
        Product $product,
        Organization $organization
    ): Response {
        $integrityValidation = $this->checkIntegrityProvider(
            $organization,
            $product,
            $fundProviderChat,
            $fundProviderChatMessage
        );

        if ($integrityValidation !== true) {
            return $this->deny('integrity');
        }

        if (!$organization->identityCan($identity, 'manage_provider_funds')) {
            return $this->deny();
        }

        return $this->allow();
    }

    /**
     * Determine whether the user can create fund provider chats.
     *
     * @param Identity $identity
     * @param FundProviderChat $fundProviderChat
     * @param FundProvider $fundProvider
     * @param Fund $fund
     * @param Organization $organization
     * @return Response
     * @noinspection PhpUnused
     */
    public function createSponsor(
        Identity $identity,
        FundProviderChat $fundProviderChat,
        FundProvider $fundProvider,
        Fund $fund,
        Organization $organization
    ): Response {
        $integrityValidation = $this->checkIntegritySponsor(
            $organization,
            $fund,
            $fundProvider,
            $fundProviderChat
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
     * Determine whether the user can create fund provider chats.
     *
     * @param Identity $identity
     * @param FundProviderChat $fundProviderChat
     * @param Product $product
     * @param Organization $organization
     * @return \Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function createProvider(
        Identity $identity,
        FundProviderChat $fundProviderChat,
        Product $product,
        Organization $organization
    ): Response {
        $integrityValidation = $this->checkIntegrityProvider(
            $organization,
            $product,
            $fundProviderChat
        );

        if ($integrityValidation !== true) {
            return $this->deny('integrity');
        }

        if (!$organization->identityCan($identity, 'manage_provider_funds')) {
            return $this->deny();
        }

        return $this->allow();
    }

    /**
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param FundProviderChat $fundProviderChat
     * @param FundProviderChatMessage|null $fundProviderChatMessage
     * @return bool
     * @noinspection PhpUnused
     */
    private function checkIntegritySponsor(
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider,
        FundProviderChat $fundProviderChat,
        ?FundProviderChatMessage $fundProviderChatMessage = null
    ): bool {
        if ($organization->id != $fund->organization_id) {
            return false;
        }

        if ($fund->id != $fundProvider->fund_id) {
            return false;
        }

        if ($fundProviderChat->fund_provider_id != $fundProvider->id) {
            return false;
        }

        if (!is_null($fundProviderChatMessage) &&
            $fundProviderChatMessage->fund_provider_chat_id != $fundProviderChat->id) {
            return false;
        }

        return true;
    }

    /**
     * @param Organization $organization
     * @param Product $product
     * @param FundProviderChat $fundProviderChat
     * @param FundProviderChatMessage|null $fundProviderChatMessage
     * @return bool
     * @noinspection PhpUnused
     */
    private function checkIntegrityProvider(
        Organization $organization,
        Product $product,
        FundProviderChat $fundProviderChat,
        ?FundProviderChatMessage $fundProviderChatMessage = null
    ): bool {
        if ($organization->id != $product->organization_id) {
            return false;
        }

        if ($fundProviderChat->product_id != $product->id) {
            return false;
        }

        if (!is_null($fundProviderChatMessage) &&
            $fundProviderChatMessage->fund_provider_chat_id != $fundProviderChat->id) {
            return false;
        }

        return true;
    }
}
