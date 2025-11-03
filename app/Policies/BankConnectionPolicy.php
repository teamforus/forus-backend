<?php

namespace App\Policies;

use App\Models\BankConnection;
use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class BankConnectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any bank connection.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_bank_connections');
    }

    /**
     * Determine whether the user can add new bank connection.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_bank_connections');
    }

    /**
     * Determine whether the user can update the bank connection.
     *
     * @param Identity $identity
     * @param BankConnection $connection
     * @param Organization $organization
     * @return bool
     */
    public function update(
        Identity $identity,
        BankConnection $connection,
        Organization $organization
    ): bool {
        if (!$this->checkIntegrity($connection, $organization)) {
            return false;
        }

        return $organization->identityCan($identity, 'manage_bank_connections');
    }

    /**
     * Determine whether the user can view the connection.
     *
     * @param Identity $identity
     * @param BankConnection $connection
     * @param Organization $organization
     * @return bool
     */
    public function view(
        Identity $identity,
        BankConnection $connection,
        Organization $organization
    ): bool {
        return $this->update($identity, $connection, $organization);
    }

    /**
     * @param BankConnection $connection
     * @param Organization $organization
     * @return bool
     */
    private function checkIntegrity(BankConnection $connection, Organization $organization): bool
    {
        return $connection->organization_id === $organization->id;
    }
}
