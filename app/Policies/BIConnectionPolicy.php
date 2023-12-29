<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Services\BIConnectionService\Models\BIConnection;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class BIConnectionPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param bool $auth2FAConfirmed
     * @return bool
     */
    public function viewAny(
        Identity $identity,
        Organization $organization,
        bool $auth2FAConfirmed = false
    ): bool {
        return $this->checkPermissionsAnd2FA($identity, $organization, $auth2FAConfirmed);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param bool $auth2FAConfirmed
     * @return mixed
     */
    public function store(
        Identity $identity,
        Organization $organization,
        bool $auth2FAConfirmed = false
    ): bool {
        return $this->checkPermissionsAnd2FA($identity, $organization, $auth2FAConfirmed) &&
            !$organization->bi_connection()->exists();
    }

    /**
     * @param Identity $identity
     * @param BIConnection $connection
     * @param Organization $organization
     * @param bool $auth2FAConfirmed
     * @return bool
     */
    public function update(
        Identity $identity,
        BIConnection $connection,
        Organization $organization,
        bool $auth2FAConfirmed = false
    ): bool {
        return $connection->organization_id === $organization->id &&
            $this->checkPermissionsAnd2FA($identity, $organization, $auth2FAConfirmed);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param bool $auth2FAConfirmed
     * @return bool
     */
    protected function checkPermissionsAnd2FA(
        Identity $identity,
        Organization $organization,
        bool $auth2FAConfirmed = false
    ): bool {
        return $organization->identityCan($identity, 'manage_bi_connection') &&
            $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * @param Identity $identity
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    protected function validate2FAFeatureRestriction(Identity $identity, bool $auth2FAConfirmed = false): Response|bool
    {
        if ($identity->load('funds')->isFeature2FARestricted('bi_connections') && !$auth2FAConfirmed) {
            return $this->deny('Invalid 2FA state.');
        }

        return true;
    }
}
