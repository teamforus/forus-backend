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
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function show(string $identity_address, Organization $organization): bool
    {
        return $organization->identityPermissions($identity_address)->count() > 0;
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showFinances(string $identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'view_finances');
    }

    /**
     * @param string $identity_address
     * @return mixed
     */
    public function store(string $identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function update(string $identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, [
            'manage_organization'
        ]);
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateIban(string $identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, [
            'manage_organization'
        ]) && $organization->identity_address === $identity_address;
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewExternalFunds(string $identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'manage_organization');
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @param Fund $externalFund
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function updateExternalFunds(
        string $identity_address,
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
     * @param string $identity_address
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     * @noinspection PhpUnused
     */
    public function updateAutoAllowReservations(string $identity_address, Organization $organization): bool
    {
        return $identity_address && ($organization->identity_address === $identity_address);
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function listSponsorProviders(string $identity_address, Organization $organization): bool
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
     * @noinspection PhpUnused
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
