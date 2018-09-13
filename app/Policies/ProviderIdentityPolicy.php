<?php

namespace App\Policies;

use App\Models\ProviderIdentity;
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
     * @return mixed
     */
    public function index($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function show($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @return bool
     */
    public function store($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param ProviderIdentity $providerIdentity
     * @return bool
     */
    public function update($identity_address, ProviderIdentity $providerIdentity) {
        return strcmp(
                $providerIdentity->organization->identity_address,
                $identity_address
            ) == 0;
    }

    /**
     * @param $identity_address
     * @param ProviderIdentity $providerIdentity
     * @return bool
     */
    public function destroy($identity_address, ProviderIdentity $providerIdentity) {
        return strcmp(
                $providerIdentity->organization->identity_address,
                $identity_address
            ) == 0;
    }
}
