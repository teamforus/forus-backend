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
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexSponsor(
        $identity_address,
        Organization $organization = null
    ) {
        return $this->storeSponsor($identity_address, $organization);
    }

    /**
     * @param $identity_address
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexProvider(
        $identity_address,
        Organization $organization = null
    ) {
        return $this->storeProvider($identity_address, $organization);
    }

    /**
     * @param $identity_address
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeSponsor(
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
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function storeProvider(
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
     * @param FundProvider $organizationFund
     * @param Organization|null $organization
     * @param Fund|null $fund
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showSponsor(
        $identity_address,
        FundProvider $organizationFund,
        Organization $organization = null,
        Fund $fund = null
    ) {
        return $this->updateSponsor($identity_address, $organizationFund, $organization, $fund);
    }

    /**
     * @param $identity_address
     * @param FundProvider $organizationFund
     * @param Organization|null $organization
     * @return bool
     */
    public function showProvider(
        $identity_address,
        FundProvider $organizationFund,
        Organization $organization = null
    ) {
        return $this->updateProvider($identity_address, $organizationFund, $organization);
    }

    /**
     * @param $identity_address
     * @param FundProvider $organizationFund
     * @param Organization|null $organization
     * @param Fund|null $fund
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateSponsor(
        $identity_address,
        FundProvider $organizationFund,
        Organization $organization = null,
        Fund $fund = null
    ) {
        if ($organization) {
            authorize('update', $organization);

            if ($organization->id != $organizationFund->fund->organization_id) {
                return false;
            }
        }

        if ($fund) {
            authorize('update', $fund);

            if ($organization && $fund->organization_id != $organization->id) {
                return false;
            }

            if ($fund->id != $organizationFund->fund_id) {
                return false;
            }
        }

        return strcmp(
                $organizationFund->fund->organization->identity_address,
                $identity_address
            ) == 0;
    }

    /**
     * @param $identity_address
     * @param FundProvider $organizationFund
     * @param Organization|null $organization
     * @return bool
     */
    public function updateProvider(
        $identity_address,
        FundProvider $organizationFund,
        Organization $organization = null
    ) {
        if ($organization) {
            if ($organization->id != $organizationFund->organization->id) {
                return false;
            }
        }

        return strcmp(
                $organizationFund->organization->identity_address,
                $identity_address
            ) == 0;
    }
}
