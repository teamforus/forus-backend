<?php

namespace App\Policies;

use App\Models\Office;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfficePolicy
{
    use HandlesAuthorization;

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAny($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'manage_offices');
    }

    /**
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnyPublic(): bool
    {
        return true;
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function store($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'manage_offices');
    }

    /**
     * @param $identity_address
     * @param Office $office
     * @param Organization $organization
     * @return bool
     */
    public function show($identity_address, Office $office, Organization $organization): bool
    {
        return $this->update($identity_address, $office, $organization);
    }

    /**
     * @param $identity_address
     * @param Office $office
     * @param Organization $organization
     * @return bool
     */
    public function update(
        $identity_address,
        Office $office,
        Organization $organization
    ): bool {
        if ($office->organization_id != $organization->id) {
            return false;
        }

        return $office->organization->identityCan($identity_address, 'manage_offices');
    }

    /**
     * @param $identity_address
     * @param Office $office
     * @param Organization $organization
     * @return bool
     */
    public function destroy(
        $identity_address,
        Office $office,
        Organization $organization 
    ): bool {
        if ($office->organization_id != $organization->id) {
            return false;
        }

        if ($organization->offices()->count() <= 1){
            return false;
        }

        return $office->organization->identityCan($identity_address, 'manage_offices');
    }
}
