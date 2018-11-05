<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\Validator;
use Illuminate\Auth\Access\HandlesAuthorization;

class ValidatorPolicy
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
     * @param Validator $validator
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        $identity_address,
        Validator $validator,
        Organization $organization = null
    ) {
        return $this->update($identity_address, $validator, $organization);
    }

    /**
     * @param $identity_address
     * @param Validator $validator
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        $identity_address,
        Validator $validator,
        Organization $organization = null
    ) {
        if ($organization) {
            authorize('update', $organization);

            if ($validator->organization_id != $organization->id) {
                return false;
            }
        }

        return strcmp(
            $validator->organization->identity_address, $identity_address) == 0;
    }

    /**
     * @param $identity_address
     * @param Validator $validator
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(
        $identity_address,
        Validator $validator,
        Organization $organization = null
    ) {
        return $this->update($identity_address, $validator, $organization);
    }
}
