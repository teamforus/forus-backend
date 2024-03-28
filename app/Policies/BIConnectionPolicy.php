<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class BIConnectionPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param bool $auth2FAConfirmed
     * @return bool|Response
     */
    public function viewAny(
        Identity $identity,
        Organization $organization,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        return $this->checkPermissionsAnd2FA($identity, $organization, $auth2FAConfirmed);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param bool $auth2FAConfirmed
     * @return bool|Response
     */
    public function store(
        Identity $identity,
        Organization $organization,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        if ($organization->bi_connection()->exists()) {
            return false;
        }

        return $this->checkPermissionsAnd2FA($identity, $organization, $auth2FAConfirmed);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param bool $auth2FAConfirmed
     * @return bool|Response
     */
    public function update(
        Identity $identity,
        Organization $organization,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        return $this->checkPermissionsAnd2FA($identity, $organization, $auth2FAConfirmed);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param bool $auth2FAConfirmed
     * @return bool|Response
     */
    protected function checkPermissionsAnd2FA(
        Identity $identity,
        Organization $organization,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        if (!$organization->identityCan($identity, 'manage_bi_connection')) {
            return false;
        }

        return $this->validate2FAFeatureRestriction($identity, $auth2FAConfirmed);
    }

    /**
     * @param Identity $identity
     * @param bool $auth2FAConfirmed
     * @return Response|bool
     */
    protected function validate2FAFeatureRestriction(
        Identity $identity,
        bool $auth2FAConfirmed = false,
    ): Response|bool {
        $isRestricted = $identity
            ->load('employees.organization')
            ->isFeature2FARestricted('bi_connections');

        return $isRestricted && !$auth2FAConfirmed ? $this->deny('Invalid 2FA state.') : true;
    }
}
