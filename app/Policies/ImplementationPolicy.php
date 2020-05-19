<?php

namespace App\Policies;

use App\Models\Implementation;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;

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
        if (!$implementation->fund_configs()->whereHas('fund',
            function(
                Builder $builder
            ) use ($organization) {
                $builder->whereIn('organization_id', (array) $organization->id);
            }
        )->count()) {
            return false;
        }

        return $organization->identityCan(
            $identity_address,
            'manage_implementation'
        );
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
        return $organization->identityCan(
            $identity_address,
            'manage_implementation'
        );
    }
}
