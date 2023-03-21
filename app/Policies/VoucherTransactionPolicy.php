<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class VoucherTransactionPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAny(Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnySponsor(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'view_finances');
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function viewAnyProvider(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'view_finances');
    }

    /**
     * @param Identity $identity
     * @param VoucherTransaction $transaction
     * @return bool
     * @noinspection PhpUnused
     */
    public function show(Identity $identity, VoucherTransaction $transaction): bool
    {
        $isOwner = $transaction->voucher->identity_address === $identity->address;
        $isPublic = $transaction->voucher->fund->public;

        return $identity->exists && ($isOwner || $isPublic);
    }

    /**
     * @param Identity $identity
     * @param VoucherTransaction $transaction
     * @param Organization|null $organization
     * @param Fund|null $fund
     * @return bool
     * @noinspection PhpUnused
     */
    public function showSponsor(
        Identity $identity,
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

        return $transaction->voucher->fund->organization->identityCan($identity, [
            'view_finances',
        ]);
    }

    /**
     * @param Identity $identity
     * @param VoucherTransaction $transaction
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showProvider(
        Identity $identity,
        VoucherTransaction $transaction,
        Organization $organization
    ): bool {
        if (!$transaction->provider || $transaction->organization_id !== $organization->id) {
            return false;
        }

        return $transaction->provider->identityCan($identity, 'view_finances');
    }

    /**
     * @param Identity $identity
     * @param VoucherTransaction $transaction
     * @param Fund $fund
     * @param Organization $organization
     * @return bool
     * @noinspection PhpUnused
     */
    public function showPublic(
        Identity $identity,
        VoucherTransaction $transaction,
        Fund $fund,
        Organization $organization
    ): bool {
        return
            $identity->exists &&
            $fund->public &&
            $fund->organization_id === $organization->id &&
            $transaction->voucher->fund_id === $fund->id;
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function storeBatchAsSponsor(
        Identity $identity,
        Organization $organization
    ): bool {
        return $organization->identityCan($identity, 'make_direct_payments');
    }
}
