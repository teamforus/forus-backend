<?php

namespace App\Policies;

use App\Models\FundProvider;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganizationFundPolicy
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
     * @return mixed
     */
    public function store($identity_address) {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @param FundProvider $organizationFund
     * @param string $state
     * @return bool
     */
    public function update(
        $identity_address,
        FundProvider $organizationFund,
        $state
    ) {
        $isFundOwner = strcmp(
                $organizationFund->fund->organization->identity_address,
                $identity_address
            ) == 0;

        $isProvider = strcmp(
                $organizationFund->organization->identity_address,
                $identity_address
            ) == 0;

        if ($isFundOwner && in_array($state, ['declined', 'approved'])) {
            return true;
        }

        if ($isProvider && in_array($state, ['abandoned'])) {
            return true;
        }

        return false;
    }
}
