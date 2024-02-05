<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
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
        return
            $organization->identityCan($identity, 'manage_payment_methods') &&
            $organization->canViewExtraPaymentsAsProvider();
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return mixed
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return
            $this->allowExtraPayments($identity, $organization) &&
            $organization->mollie_connection()->doesntExist();
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function update(Identity $identity, Organization $organization): bool
    {
        return
            $this->allowExtraPayments($identity, $organization) &&
            $organization->mollie_connection()->exists();
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function connectMollieAccount(Identity $identity, Organization $organization): bool
    {
        return $this->store($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function fetchMollieAccount(Identity $identity, Organization $organization): bool
    {
        return
            $organization->identityCan($identity, 'manage_payment_methods') &&
            $organization->mollie_connection()->exists();
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function destroy(Identity $identity, Organization $organization): bool {
        return
            $organization->identityCan($identity, 'manage_payment_methods') &&
            $organization->mollie_connection()->exists();
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
            $organization->canUseExtraPaymentsAsProvider();
    }
}
