<?php

namespace App\Policies;

use App\Models\Implementation;
use App\Models\Organization;
use App\Scopes\Builders\ImplementationQuery;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImplementationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any implementations.
     *
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(
        $identity_address,
        Organization $organization
    ) {
        return $organization->identityCan(
            $identity_address, 'manage_implementation'
        );
    }

    /**
     * Determine whether the user can view the implementation.
     *
     * @param $identity_address
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     */
    public function view(
        $identity_address,
        Implementation $implementation,
        Organization $organization
    ) {
        return $this->update($identity_address, $implementation, $organization);
    }

    /**
     * Determine whether the user can update the implementation.
     *
     * @param $identity_address
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     */
    public function update(
        $identity_address,
        Implementation $implementation,
        Organization $organization
    ) {
        if (!$this->checkIntegrity($implementation, $organization)){
            return false;
        }

        return $organization->identityCan(
            $identity_address,
            'manage_implementation'
        );
    }

    /**
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     */
    private function checkIntegrity(
        Implementation $implementation,
        Organization $organization
    ) {
        return ImplementationQuery::whereOrganizationIdFilter(
            Implementation::query(),
            $organization->id
        )->where('id', $implementation->id)->exists();
    }
}
