<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Auth\Access\HandlesAuthorization;

class MollieConnectionPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $identity->exists && $organization->can_view_provider_extra_payments;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return mixed
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $this->allowExtraPayments($identity, $organization) ||
            !$organization->mollie_connection_configured()->exists();
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function connectMollieAccount(Identity $identity, Organization $organization): bool
    {
        return $this->allowExtraPayments($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function fetchMollieAccount(Identity $identity, Organization $organization): bool
    {
        return $this->allowExtraPayments($identity, $organization) &&
            $organization->mollie_connection_configured()->exists();
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function allowExtraPayments(Identity $identity, Organization $organization): bool
    {
        return $identity->exists && $organization->allow_extra_payments_by_sponsor;
    }

    /**
     * @param Identity $identity
     * @param MollieConnection $connection
     * @param Organization $organization
     * @return bool
     */
    public function destroy(
        Identity $identity,
        MollieConnection $connection,
        Organization $organization
    ): bool {
        return $identity->address === $organization->identity_address &&
            $organization->can_view_provider_extra_payments &&
            $connection->organization_id === $organization->id;
    }
}
