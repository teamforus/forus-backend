<?php

namespace App\Policies;

use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationPolicy
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
     * @return mixed
     */
    public function index($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function show(
        $identity_address,
        Organization $organization
    ) {
        return $this->update($identity_address, $organization);
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function store($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function update($identity_address, Organization $organization) {
        return strcmp($organization->identity_address, $identity_address) == 0;
    }
}
