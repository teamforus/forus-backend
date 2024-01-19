<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Services\MollieService\Models\MollieConnectionProfile;
use Illuminate\Auth\Access\HandlesAuthorization;

class MollieConnectionProfilePolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return mixed
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $this->allowExtraPayments($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param MollieConnectionProfile $profile
     * @param Organization $organization
     * @return bool
     */
    public function update(
        Identity $identity,
        MollieConnectionProfile $profile,
        Organization $organization,
    ): bool {
        return
            $this->store($identity, $organization) &&
            $profile->mollie_connection_id == $organization->mollie_connection?->id;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function allowExtraPayments(Identity $identity, Organization $organization): bool
    {
        return
            $organization->identityCan($identity, 'manage_payment_methods') &&
            $organization->mollie_connection()->exists() &&
            $organization->canUseExtraPaymentsAsProvider();
    }
}
