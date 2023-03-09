<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundProviderPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @param Fund|null $fund
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnySponsor(
        Identity $identity,
        Organization $organization,
        Fund $fund = null
    ): bool {
        if ($fund && ($fund->organization_id != $organization->id)) {
            return false;
        }

        if ($fund && $fund->public) {
            return true;
        }

        return $organization->identityCan($identity, [
            'view_finances', 'manage_providers'
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnyProvider(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [
            'manage_provider_funds', 'scan_vouchers',
        ], false);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function storeSponsor(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_providers');
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function storeProvider(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'manage_provider_funds');
    }

    /**
     * @param Identity $identity
     * @param FundProvider $fundProvider
     * @param Organization $organization
     * @param Fund $fund
     * @return bool
     * @noinspection PhpUnused
     */
    public function showSponsor(
        Identity $identity,
        FundProvider $fundProvider,
        Organization $organization,
        Fund $fund
    ): bool {
        if ($organization->id != $fundProvider->fund->organization_id) {
            return false;
        }

        if ($fund->id != $fundProvider->fund_id) {
            return false;
        }

        if ($fund->public) {
            return true;
        }

        return $organization->identityCan($identity, [
            'view_finances', 'manage_providers',
        ], false);
    }

    /**
     * @param Identity $identity
     * @param FundProvider $organizationFund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showProvider(
        Identity $identity,
        FundProvider $organizationFund,
        Organization $organization
    ): bool {
        if ($organization->id != $organizationFund->organization_id) {
            return false;
        }

        return $organizationFund->organization->identityCan($identity, 'manage_provider_funds');
    }

    /**
     * @param Identity $identity
     * @param FundProvider $fundProvider
     * @param Organization $organization
     * @param Fund $fund
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateSponsor(
        Identity $identity,
        FundProvider $fundProvider,
        Organization $organization,
        Fund $fund
    ): bool {
        if ($organization->id != $fundProvider->fund->organization_id) {
            return false;
        }

        if ($fund->id != $fundProvider->fund_id) {
            return false;
        }

        return !$fund->isArchived() && $fund->organization->identityCan($identity, [
            'view_finances', 'manage_providers',
        ], false);
    }

    /**
     * @param Identity $identity
     * @param FundProvider $organizationFund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function updateProvider(
        Identity $identity,
        FundProvider $organizationFund,
        Organization $organization
    ): bool {
        if ($organization->id != $organizationFund->organization_id) {
            return false;
        }

        return $organizationFund->organization->identityCan($identity, 'manage_provider_funds');
    }

    /**
     * @param Identity $identity
     * @param FundProvider $fundProvider
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function deleteProvider(
        Identity $identity,
        FundProvider $fundProvider,
        Organization $organization
    ): bool {
        $isPending = !$fundProvider->isApproved();
        $hasPermission = $this->updateProvider($identity, $fundProvider, $organization);
        $doesntHaveTransactions = !$fundProvider->hasTransactions();

        return $isPending && $hasPermission && $doesntHaveTransactions;
    }
}
