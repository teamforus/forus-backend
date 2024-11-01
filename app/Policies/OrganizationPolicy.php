<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;

class OrganizationPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity|null $identity
     * @return bool
     */
    public function viewAny(?Identity $identity): bool
    {
        return !$identity || $identity->exists;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function show(Identity $identity, Organization $organization): bool
    {
        return $organization->isEmployee($identity);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showFinances(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'view_finances');
    }

    /**
     * @param Identity $identity
     * @return mixed
     */
    public function store(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function update(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_organization');
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateIban(Identity $identity, Organization $organization): bool
    {
        return $this->update($identity, $organization) && $organization->isOwner($identity);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateAutoAllowReservations(Identity $identity, Organization $organization): bool
    {
        return $organization->isOwner($identity);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function listSponsorProviders(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [
            'manage_providers', 'view_finances'
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param Organization $provider
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewSponsorProvider(
        Identity $identity,
        Organization $organization,
        Organization $provider
    ): bool {
        return $organization->whereHas('funds.providers', function(Builder $builder) use ($provider) {
            $builder->where('organization_id', $provider->id);
        })->exists() && $this->listSponsorProviders($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function transferOwnership(Identity $identity, Organization $organization): bool
    {
        return $organization->isOwner($identity);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function showFeatures(Identity $identity, Organization $organization): bool
    {
        $hasFunds = Employee::query()
            ->where('identity_address', $identity->address)
            ->whereHas('roles.permissions')
            ->whereRelation('organization.funds.fund_config', 'is_configured', true)
            ->exists();

         return $organization->isEmployee($identity) && $hasFunds;
    }
}
