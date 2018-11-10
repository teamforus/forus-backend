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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexSponsor(
        string $identity_address,
        Organization $organization = null
    ) {
        if ($organization) {
            authorize('update', $organization);
        }

        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function indexProvider(
        string $identity_address,
        Organization $organization = null
    ) {
        if ($organization) {
            authorize('update', $organization);
        }

        return !empty($identity_address);
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
        return !empty($identity_address) && strcmp(
                $transaction->voucher->identity_address, $identity_address
            ) == 0;
    }

    /**
     * @param string $identity_address
     * @param VoucherTransaction $transaction
     * @param Organization|null $organization
     * @param Fund|null $fund
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showSponsor(
        string $identity_address,
        VoucherTransaction $transaction,
        Organization $organization = null,
        Fund $fund = null
    ) {
        if ($organization) {
            authorize('update', $organization);

            if ($transaction->voucher->fund->organization_id != $organization->id) {
                return false;
            }
        }

        if ($fund) {
            authorize('update', $fund);

            if ($transaction->voucher->fund_id != $fund->id) {
                return false;
            }
        }

        return strcmp(
                $transaction->voucher->fund->organization->identity_address,
                $identity_address
            ) == 0;
    }

    /**
     * @param string $identity_address
     * @param VoucherTransaction $transaction
     * @param Organization|null $organization
     * @return bool
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showProvider(
        string $identity_address,
        VoucherTransaction $transaction,
        Organization $organization = null
    ) {
        if ($organization) {
            authorize('update', $organization);

            if ($transaction->organization_id != $organization->id) {
                return false;
            }
        }

        return strcmp(
                $transaction->organization->identity_address,
                $identity_address
            ) == 0;
    }
}
