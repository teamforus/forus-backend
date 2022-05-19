<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class VoucherTransactionPolicy
 * @package App\Policies
 */
class VoucherTransactionPolicy
{
    use HandlesAuthorization;

    /**
     * @param string $identity_address
     * @return bool
     */
    public function viewAny(
        string $identity_address
    ): bool {
        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAnySponsor(
        string $identity_address,
        Organization $organization
    ): bool {
        return $organization->identityCan($identity_address, 'view_finances');
    }

    /**
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAnyProvider(
        string $identity_address,
        Organization $organization
    ): bool {
        return $organization->identityCan($identity_address, 'view_finances');
    }

    /**
     * @param string $identity_address
     * @param VoucherTransaction $transaction
     * @return bool
     */
    public function show(
        string $identity_address,
        VoucherTransaction $transaction
    ): bool {
        return !empty($identity_address) && ((strcmp(
            $transaction->voucher->identity_address, $identity_address
        ) === 0) || $transaction->voucher->fund->public);
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
    ): bool {
        if ($organization) {
            if ($transaction->voucher->fund->organization_id !== $organization->id) {
                return false;
            }

            if ($fund && ($transaction->voucher->fund_id !== $fund->id)) {
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
    ): bool {
        if ($organization && ($transaction->organization_id !== $organization->id)) {
            return false;
        }

        return $transaction->provider->identityCan($identity_address, 'view_finances');
    }

    /**
     * @param string $identity_address
     * @param VoucherTransaction $transaction
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showPublic(
        string $identity_address,
        VoucherTransaction $transaction,
        Fund $fund,
        Organization $organization
    ): bool {
        // identity_address not required
        return isset($identity_address) && $fund->public && (
            $fund->organization_id === $organization->id) && (
                $transaction->voucher->fund_id === $fund->id);
    }
}
