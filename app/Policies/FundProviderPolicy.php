<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class FundProviderPolicy
 * @package App\Policies
 */
class FundProviderPolicy
{
    use HandlesAuthorization;

    /**
     * @param $identity_address
     * @param Organization $organization
     * @param Fund|null $fund
     * @return bool
     */
    public function viewAnySponsor(
        $identity_address,
        Organization $organization,
        Fund $fund = null
    ): bool {
        if ($fund && $organization && ($fund->organization_id != $organization->id)) {
            return false;
        }

        if ($fund && $fund->public) {
            return true;
        }

        return $identity_address && $organization->identityCan($identity_address, [
            'view_finances', 'manage_providers'
        ], false);
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAnyProvider($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, [
            'manage_provider_funds', 'scan_vouchers'
        ], false);
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function storeSponsor($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'manage_providers');
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function storeProvider($identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'manage_provider_funds');
    }

    /**
     * @param $identity_address
     * @param FundProvider $fundProvider
     * @param Organization $organization
     * @param Fund $fund
     * @return bool
     */
    public function showSponsor(
        $identity_address,
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

        return $identity_address && $organization->identityCan($identity_address, [
            'manage_funds', 'view_finances'
        ], false);
    }

    /**
     * @param $identity_address
     * @param FundProvider $organizationFund
     * @param Organization $organization
     * @return bool
     */
    public function showProvider(
        $identity_address,
        FundProvider $organizationFund,
        Organization $organization
    ): bool {
        if ($organization->id != $organizationFund->organization_id) {
            return false;
        }

        return $organizationFund->organization->identityCan($identity_address, 'manage_provider_funds');
    }

    /**
     * @param $identity_address
     * @param FundProvider $organizationFund
     * @param Organization|null $organization
     * @param Fund|null $fund
     * @return bool
     */
    public function updateSponsor(
        $identity_address,
        FundProvider $organizationFund,
        Organization $organization,
        Fund $fund
    ): bool {
        if ($organization->id != $organizationFund->fund->organization_id) {
            return false;
        }

        if ($fund->id != $organizationFund->fund_id) {
            return false;
        }

        return $organizationFund->fund->organization->identityCan($identity_address, 'manage_funds');
    }

    /**
     * @param $identity_address
     * @param FundProvider $organizationFund
     * @param Organization|null $organization
     * @param Fund|null $fund
     * @return bool
     */
    public function deleteSponsor(
        $identity_address,
        FundProvider $organizationFund,
        Organization $organization,
        Fund $fund
    ): bool {
        return $this->updateSponsor($identity_address, $organizationFund, $organization, $fund);
    }

    /**
     * @param $identity_address
     * @param FundProvider $organizationFund
     * @param Organization $organization
     * @return bool
     */
    public function updateProvider(
        $identity_address,
        FundProvider $organizationFund,
        Organization $organization
    ): bool {
        if ($organization->id != $organizationFund->organization_id) {
            return false;
        }

        return $organizationFund->organization->identityCan($identity_address, 'manage_provider_funds');
    }

    /**
     * @param $identity_address
     * @param FundProvider $fundProvider
     * @param Organization $organization
     * @return bool
     */
    public function deleteProvider(
        $identity_address,
        FundProvider $fundProvider,
        Organization $organization
    ): bool {
        $isPending = !$fundProvider->isApproved();
        $hasPermission = $this->updateProvider($identity_address, $fundProvider, $organization);
        $doesntHaveTransactions = !$fundProvider->hasTransactions();

        return $isPending && $hasPermission && $doesntHaveTransactions;
    }
}
