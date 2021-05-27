<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\Organization;
use App\Scopes\Builders\FundQuery;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;

class OrganizationPolicy
{
    use HandlesAuthorization;

    /**
     * @return mixed
     */
    public function viewAny(): bool
    {
        return true;
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function show($identity_address, Organization $organization): bool
    {
        return $organization->identityPermissions($identity_address)->count() > 0;
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function showFinances($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'view_finances');
    }

    /**
     * @param $identity_address
     * @return mixed
     */
    public function store($identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function update($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, [
            'manage_organization'
        ]);
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewExternalFunds($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'manage_organization');
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @param Fund $externalFund
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function updateExternalFunds(
        $identity_address,
        Organization $organization,
        Fund $externalFund
    ) {
        if (!FundQuery::whereExternalValidatorFilter(
            Fund::query(), $organization->id
        )->where('funds.id', $externalFund->id)->exists()) {
            return $this->deny("Invalid fund id.");
        }

        return $organization->identityCan($identity_address, 'manage_organization');
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function listSponsorProviders($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, [
            'manage_providers', 'view_finances'
        ], false);
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @param Organization $provider
     * @return bool
     */
    public function viewSponsorProvider(
        string $identity_address,
        Organization $organization,
        Organization $provider
    ): bool {
        return $organization->whereHas('funds.providers', function(Builder $builder) use ($provider) {
            $builder->where('organization_id', $provider->id);
        })->exists() && $this->listSponsorProviders($identity_address, $organization);
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function transferOwnership(string $identity_address, Organization $organization): bool
    {
        return $identity_address && ($organization->identity_address === $identity_address);
    }
}
