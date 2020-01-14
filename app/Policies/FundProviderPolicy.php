<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundProviderPolicy
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
     * @param Organization $organization
     * @param Fund|null $fund
     * @return bool
     */
    public function viewAnySponsor(
        $identity_address,
        Organization $organization,
        Fund $fund = null
    ) {
        if ($fund && $organization ? (
            $fund->organization_id != $organization->id
        ) : false) {
            return false;
        }

        if ($fund && $fund->public) {
            return true;
        }

        return $identity_address &&
            $organization->identityCan($identity_address, [
            'view_finances', 'manage_providers'
        ], false);
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAnyProvider(
        $identity_address,
        Organization $organization
    ) {
        return $organization->identityCan($identity_address, [
            'manage_provider_funds'
        ], false);
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function storeSponsor(
        $identity_address,
        Organization $organization
    ) {
        return $organization->identityCan($identity_address, [
            'manage_providers'
        ], false);
    }

    /**
     * @param $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function storeProvider(
        $identity_address,
        Organization $organization
    ) {
        return $organization->identityCan($identity_address, [
            'manage_provider_funds'
        ], false);
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
    ) {
        if ($organization->id != $fundProvider->fund->organization_id) {
            return false;
        }

        if ($fund->id != $fundProvider->fund_id) {
            return false;
        }

        if ($fund->public) {
            return true;
        }

        return $identity_address && $organization->identityCan(
            $identity_address, [
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
    ) {
        if ($organization->id != $organizationFund->organization_id) {
            return false;
        }

        return $organizationFund->organization->identityCan($identity_address, [
            'manage_provider_funds'
        ], false);
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
    ) {
        if ($organization->id != $organizationFund->fund->organization_id) {
            return false;
        }

        if ($fund->id != $organizationFund->fund_id) {
            return false;
        }

        return $organizationFund->fund->organization->identityCan($identity_address, [
            'manage_funds'
        ]);
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
    ) {
        if ($organization->id != $organizationFund->organization_id) {
            return false;
        }

        return $organizationFund->organization->identityCan($identity_address, [
            'manage_provider_funds'
        ]);
    }
}
