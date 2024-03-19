<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Office;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class OfficePolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Office $office
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function update(Identity $identity, Office $office, Organization $organization): bool
    {
        if ($office->organization_id != $organization->id) {
            return false;
        }

        return $office->organization->identityCan($identity, 'manage_offices');
    }
}
