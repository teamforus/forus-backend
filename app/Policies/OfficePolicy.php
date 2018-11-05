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
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        $identity_address,
        Organization $organization = null
    ) {
        return $this->store($identity_address, $organization);
    }

    /**
     * @param $identity_address
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        $identity_address,
        Organization $organization = null
    ) {
        if ($organization) {
            authorize('update', $organization);
        }

        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param Office $office
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        $identity_address,
        Office $office,
        Organization $organization = null
    ) {
        return $this->update($identity_address, $office, $organization);
    }

    /**
     * @param $identity_address
     * @param Office $office
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        $identity_address,
        Office $office,
        Organization $organization = null
    ) {
        if ($organization) {
            authorize('update', $organization);

            if ($office->organization_id != $organization->id) {
                return false;
            }
        }

        return strcmp(
                $office->organization->identity_address, $identity_address) == 0;
    }

    /**
     * @param $identity_address
     * @param Office $office
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(
        $identity_address,
        Office $office,
        Organization $organization = null
    ) {
        return $this->update($identity_address, $office, $organization);
    }
}
