<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Services\MollieService\Models\MollieConnection;
use App\Services\MollieService\Models\MollieConnectionProfile;
use Illuminate\Auth\Access\HandlesAuthorization;

class MollieConnectionProfilePolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param MollieConnection $connection
     * @return mixed
     */
    public function store(
        Identity $identity,
        MollieConnection $connection,
        Organization $organization
    ): bool {
        return $this->allowExtraPayments($identity, $organization) &&
            $organization->mollie_connection_configured()->where('id', $connection->id)->exists();
    }

    /**
     * @param Identity $identity
     * @param MollieConnectionProfile $profile
     * @param MollieConnection $connection
     * @param Organization $organization
     * @return bool
     */
    public function update(
        Identity $identity,
        MollieConnectionProfile $profile,
        MollieConnection $connection,
        Organization $organization
    ): bool {
        return $this->store($identity, $connection, $organization) &&
            $profile->mollie_connection_id === $connection->id;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function allowExtraPayments(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_payment_methods') &&
            $organization->allow_extra_payments_by_sponsor;
    }
}
