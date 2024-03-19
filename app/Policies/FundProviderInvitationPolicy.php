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
}
