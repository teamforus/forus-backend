<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class ImplementationPolicy
{
    use HandlesAuthorization;

    /**
     * @param Implementation $implementation
     * @param Organization $organization
     * @return bool
     */
    private function checkIntegrity(
        Implementation $implementation,
        Organization $organization
    ): bool {
        return $implementation->organization_id === $organization->id;
    }
}
