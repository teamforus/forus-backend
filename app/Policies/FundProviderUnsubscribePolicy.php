<?php

namespace App\Policies;

use App\Models\FundProviderUnsubscribe;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundProviderUnsubscribePolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnySponsor(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, Permission::MANAGE_PROVIDERS);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnyProvider(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, Permission::MANAGE_PROVIDER_FUNDS);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, Permission::MANAGE_PROVIDER_FUNDS);
    }

    /**
     * @param Identity $identity
     * @param FundProviderUnsubscribe $fundProviderUnsubscribe
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function show(
        Identity $identity,
        FundProviderUnsubscribe $fundProviderUnsubscribe,
        Organization $organization,
    ): bool {
        if ($fundProviderUnsubscribe->fund_provider->organization_id != $organization->id) {
            return false;
        }

        return $organization->identityCan($identity, Permission::MANAGE_PROVIDER_FUNDS);
    }

    /**
     * @param Identity $identity
     * @param FundProviderUnsubscribe $fundProviderUnsubscribe
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function cancel(
        Identity $identity,
        FundProviderUnsubscribe $fundProviderUnsubscribe,
        Organization $organization,
    ): bool {
        if ($fundProviderUnsubscribe->fund_provider->organization_id != $organization->id) {
            return false;
        }

        return $fundProviderUnsubscribe->isPending() && $organization->identityCan($identity, [
            Permission::MANAGE_PROVIDER_FUNDS,
        ]);
    }
}
