<?php

namespace App\Policies;

use App\Models\ProviderIdentity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProviderIdentityPolicy
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
     * @param ProviderIdentity $providerIdentity
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        $identity_address,
        ProviderIdentity $providerIdentity,
        Organization $organization = null
    ) {
        return $this->update($identity_address, $providerIdentity, $organization);
    }

    /**
     * @param $identity_address
     * @param ProviderIdentity $providerIdentity
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        $identity_address,
        ProviderIdentity $providerIdentity,
        Organization $organization = null
    ) {
        if ($organization) {
            authorize('update', $organization);

            if ($providerIdentity->provider_id != $organization->id) {
                return false;
            }
        }

        return strcmp(
                $providerIdentity->organization->identity_address, $identity_address) == 0;
    }

    /**
     * @param $identity_address
     * @param ProviderIdentity $providerIdentity
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(
        $identity_address,
        ProviderIdentity $providerIdentity,
        Organization $organization = null
    ) {
        return $this->update($identity_address, $providerIdentity, $organization);
    }
}
