<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class VoucherTransactionPolicy
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
     * @return bool
     */
    public function index(
        string $identity_address
    ) {
        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param Organization|null $organization
     * @return bool
     */
    public function indexSponsor(
        string $identity_address,
        Organization $organization = null
    ) {
        if ($organization) {
            return $organization->identityCan(
                $identity_address, 'view_finances'
            );
        }

        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param Organization|null $organization
     * @return bool
     */
    public function indexProvider(
        string $identity_address,
        Organization $organization = null
    ) {
        if ($organization) {
            return $organization->identityCan(
                $identity_address, 'view_finances'
            );
        }

        return !empty($identity_address);
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
     * @param VoucherTransaction $transaction
     * @return bool
     */
    public function show(
        string $identity_address,
        VoucherTransaction $transaction
    ) {
        return !empty($identity_address) && ((strcmp(
            $transaction->voucher->identity_address, $identity_address
        ) == 0) || $transaction->voucher->fund->public);
    }

    /**
     * @param string $identity_address
     * @param VoucherTransaction $transaction
     * @param Organization|null $organization
     * @param Fund|null $fund
     * @return bool
     */
    public function showSponsor(
        string $identity_address,
        VoucherTransaction $transaction,
        Organization $organization = null,
        Fund $fund = null
    ) {
        if ($organization) {
            if ($transaction->voucher->fund->organization_id != $organization->id) {
                return false;
            }

            if ($fund && ($transaction->voucher->fund_id != $fund->id)) {
                return false;
            }
        }

        return $transaction->voucher->fund->organization->identityCan(
            $identity_address, 'view_finances'
        );
    }

    /**
     * @param string $identity_address
     * @param VoucherTransaction $transaction
     * @param Organization|null $organization
     * @return bool
     */
    public function showProvider(
        string $identity_address,
        VoucherTransaction $transaction,
        Organization $organization = null
    ) {
        if ($organization) {
            if ($transaction->organization_id != $organization->id) {
                return false;
            }
        }

        return $transaction->provider->identityCan(
            $identity_address, 'view_finances'
        );
    }

    /**
     * @param string $identity_address
     * @param VoucherTransaction $transaction
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     */
    public function showPublic(
        string $identity_address,
        VoucherTransaction $transaction,
        Fund $fund,
        Organization $organization
    ) {
        // identity_address not required
        return isset($identity_address) && $fund->public && (
            $fund->organization_id == $organization->id) && (
                $transaction->voucher->fund_id == $fund->id);
    }
}
