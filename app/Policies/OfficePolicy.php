<?php

namespace App\Policies;

use App\Models\Office;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class OfficePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function index(
        $identity_address,
        Organization $organization 
    ) {
        return $organization->identityCan(
            $identity_address,
            'manage_offices'
        );
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function store(
        $identity_address,
        Organization $organization 
    ) {
        return $organization->identityCan(
            $identity_address,
            'manage_offices'
        );
    }

    /**
     * @param $identity_address
     * @param Office $office
     * @param Organization $organization
     * @return bool
     */
    public function show(
        $identity_address,
        Office $office,
        Organization $organization 
    ) {
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
    ) {
        if ($office->organization_id != $organization->id) {
            return false;
        }

        return $office->organization->identityCan(
            $identity_address,
            'manage_offices'
        );
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
    ) {
        return $this->update($identity_address, $office, $organization);
    }
}
