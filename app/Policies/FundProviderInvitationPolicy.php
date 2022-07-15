<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundProviderInvitation;
use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FundProviderInvitationPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnySponsor(Identity $identity, Fund $fund, Organization $organization): bool
    {
        if ($fund->organization_id != $organization->id) {
            return false;
        }

        return $organization->identityCan($identity, [
            'manage_providers', 'manage_funds'
        ]);
    }

    /**
     * @param Identity $identity
     * @param FundProviderInvitation $fundProviderInvitation
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showSponsor(
        Identity $identity,
        FundProviderInvitation $fundProviderInvitation,
        Fund $fund,
        Organization $organization
    ): bool {
        if ($fund->organization_id != $organization->id) {
            return false;
        }

        if ($fundProviderInvitation->fund_id != $fund->id) {
            return false;
        }

        return $organization->identityCan($identity, [
            'manage_providers', 'manage_funds'
        ]);
    }

    /**
     * @param Identity $identity
     * @param Fund $fromFund
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function storeSponsor(
        Identity $identity,
        Fund $fromFund,
        Fund $fund,
        Organization $organization
    ): bool {
        if ($fund->organization_id != $fromFund->organization_id) {
            return false;
        }

        if ($fund->organization_id != $organization->id) {
            return false;
        }

        return $organization->identityCan($identity, [
            'manage_providers', 'manage_funds'
        ]);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnyProvider(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_provider_funds');
    }

    /**
     * @param Identity $identity
     * @param FundProviderInvitation $invitation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showProvider(
        Identity $identity,
        FundProviderInvitation $invitation,
        Organization $organization
    ): bool {
        return $this->acceptProvider($identity, $invitation, $organization);
    }

    /**
     * @param Identity $identity
     * @param FundProviderInvitation $invitation
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function acceptProvider(
        Identity $identity,
        FundProviderInvitation $invitation,
        Organization $organization
    ): bool {
        if ($organization->id != $invitation->organization_id) {
            return false;
        }

        return $organization->identityCan($identity, 'manage_provider_funds');
    }

    /**
     * Determine whether the user can view the fund provider invitation.
     *
     * @param Identity $identity
     * @param FundProviderInvitation $invitation
     * @return bool
     * @noinspection PhpUnused
     */
    public function showByToken(Identity $identity, FundProviderInvitation $invitation): bool
    {
        return $identity->exists && $invitation->exists;
    }

    /**
     * Determine whether the user can accept the fund provider invitation.
     *
     * @param Identity $identity
     * @param FundProviderInvitation $invitation
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function acceptFundProviderInvitation(
        Identity $identity,
        FundProviderInvitation $invitation
    ): Response|bool {
        if ($invitation->state == FundProviderInvitation::STATE_ACCEPTED) {
            return $this->deny("Invitation already approved!");
        }

        if ($invitation->state == FundProviderInvitation::STATE_EXPIRED) {
            return $this->deny("Invitation expired!");
        }

        return $identity->exists && $invitation->state == FundProviderInvitation::STATE_PENDING;
    }
}
