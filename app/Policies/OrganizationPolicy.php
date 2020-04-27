<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\Organization;
use App\Scopes\Builders\FundQuery;
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
    public function viewAny($identity_address) {
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
        return $organization->identityPermissions($identity_address)->count() > 0;
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
        return $organization->identityCan($identity_address, [
            'manage_organization'
        ]);
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewExternalFunds($identity_address, Organization $organization) {
        return $organization->identityCan($identity_address, [
            'manage_organization'
        ]);
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @param Fund $externalFund
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function updateExternalFunds(
        $identity_address,
        Organization $organization,
        Fund $externalFund
    ) {
        if (!FundQuery::whereExternalValidatorFilter(
            Fund::query(),
            $organization->id
        )->where('funds.id', $externalFund->id)->exists()) {
            return $this->deny("Invalid fund id.");
        }

        return $organization->identityCan($identity_address, [
            'manage_organization'
        ]);
    }
}
