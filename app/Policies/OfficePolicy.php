<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Office;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfficePolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_offices');
    }

    /**
     * @param Identity|null $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnyPublic(?Identity $identity): bool
    {
        return !$identity || $identity->exists;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function store(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_offices');
    }

    /**
     * @param Identity $identity
     * @param Office $office
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function show(Identity $identity, Office $office, Organization $organization): bool
    {
        return $this->update($identity, $office, $organization);
    }

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

    /**
     * @param Identity $identity
     * @param Office $office
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function destroy(Identity $identity, Office $office, Organization $organization): bool
    {
        if ($office->organization_id != $organization->id) {
            return false;
        }

        if ($organization->offices()->count() <= 1){
            return false;
        }

        return $office->organization->identityCan($identity, 'manage_offices');
    }
}
