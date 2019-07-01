<?php

namespace App\Policies;

use App\Models\BunqMeTab;
use App\Models\Fund;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class BunqMeTabPolicy
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
     * @param string $identity_address
     * @param Fund|null $fund
     * @param Organization|null $organization
     * @return bool
     */
    public function indexPublic(
        string $identity_address,
        Fund $fund,
        Organization $organization
    ) {
        // identity_address not required
        return isset($identity_address) && $fund->public && (
            $fund->organization_id == $organization->id);
    }

    /**
     * @param string $identity_address
     * @param BunqMeTab $bunqMeTab
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function showPublic(
        string $identity_address,
        BunqMeTab $bunqMeTab,
        Fund $fund,
        Organization $organization
    ) {
        // identity_address not required
        return isset($identity_address) && $fund->public && (
            $fund->organization_id == $organization->id) && (
                $bunqMeTab->fund_id == $fund->id) && (
                $bunqMeTab->status == BunqMeTab::STATUS_PAID);
    }
}
