<?php

namespace App\Policies;

use App\Models\BIConnection;
use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class BIConnectionPolicy
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
        return $organization->identityCan($identity, 'manage_export_api_connections')
            && $organization->allow_bi_connection;
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
        return $organization->identityCan($identity, 'manage_export_api_connections')
            && !$organization->bIConnections()->exists() && $organization->allow_bi_connection;
    }

    /**
     * @param Identity $identity
     * @param BIConnection $connection
     * @param Organization $organization
     * @return bool
     */
    public function update(
        Identity $identity,
        BIConnection $connection,
        Organization $organization
    ): bool {
        return $organization->identityCan($identity, 'manage_export_api_connections')
            && $organization->allow_bi_connection;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function recreate(
        Identity $identity,
        Organization $organization
    ): bool {
        return $organization->identityCan($identity, 'manage_export_api_connections')
            && $organization->bIConnections()->exists() && $organization->allow_bi_connection;
    }

    /**
     * Determine whether the user can view the connection.
     *
     * @param Identity $identity
     * @param BIConnection $connection
     * @param Organization $organization
     * @return bool
     */
    public function view(
        Identity $identity,
        BIConnection $connection,
        Organization $organization
    ): bool {
        return $this->viewAny($identity, $organization);
    }
}
