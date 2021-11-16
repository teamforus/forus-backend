<?php

namespace App\Policies;

use App\Models\BankConnection;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class BankConnectionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any bank connection.
     *
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAny($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'manage_bank_connections');
    }

    /**
     * Determine whether the user can add new bank connection.
     *
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function store($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'manage_bank_connections');
    }

    /**
     * Determine whether the user can update the bank connection.
     *
     * @param $identity_address
     * @param BankConnection $connection
     * @param Organization $organization
     * @return bool
     */
    public function update(
        $identity_address,
        BankConnection $connection,
        Organization $organization
    ): bool {
        if (!$this->checkIntegrity($connection, $organization)) {
            return false;
        }

        return $organization->identityCan($identity_address, 'manage_bank_connections');
    }

    /**
     * Determine whether the user can view the connection.
     *
     * @param $identity_address
     * @param BankConnection $connection
     * @param Organization $organization
     * @return bool
     */
    public function view(
        $identity_address,
        BankConnection $connection,
        Organization $organization
    ): bool {
        return $this->update($identity_address, $connection, $organization);
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
