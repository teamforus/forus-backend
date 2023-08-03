<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationReservationFieldPolicy
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
        return $identity->exists && $organization->allow_reservation_custom_fields;
    }
}
