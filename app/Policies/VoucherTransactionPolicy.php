<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\VoucherTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

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
        $isOwner = $transaction->voucher->identity_id === $identity->id;
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

        return $transaction->voucher->fund->organization->identityCan($identity, 'view_finances');
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
    public function storeAsSponsor(
        Identity $identity,
        Organization $organization,
    ): bool {
        return $organization->identityCan($identity, 'make_direct_payments');
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
        return $this->storeAsSponsor($identity, $organization);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAnyPayoutsSponsor(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, [Permission::MANAGE_PAYOUTS]);
    }

    /**
     * @param Identity $identity
     * @param VoucherTransaction $transaction
     * @param Organization|null $organization
     * @return bool|Response
     */
    public function showPayoutSponsor(
        Identity $identity,
        VoucherTransaction $transaction,
        Organization $organization = null,
    ): bool|Response {
        if ($transaction->voucher?->fund?->organization_id !== $organization->id) {
            return false;
        }

        if (!$transaction->targetIsPayout()) {
            return $this->deny('Not payout transaction.');
        }

        return $organization->identityCan($identity, Permission::MANAGE_PAYOUTS);
    }

    /**
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function storePayoutsSponsor(
        Identity $identity,
        Organization $organization,
    ): bool {
        return $organization->identityCan($identity, Permission::MANAGE_PAYOUTS);
    }

    /**
     * @param Identity $identity
     * @param VoucherTransaction $transaction
     * @param Organization $organization
     * @return bool|Response
     */
    public function updatePayoutsSponsor(
        Identity $identity,
        VoucherTransaction $transaction,
        Organization $organization,
    ): bool|Response {
        if ($transaction->voucher?->fund?->organization_id !== $organization->id) {
            return false;
        }

        if (!$transaction->targetIsPayout()) {
            return $this->deny('Not payout transaction.');
        }

        return $transaction->voucher->fund->organization->identityCan($identity, [
            Permission::MANAGE_PAYOUTS,
        ]) && $transaction->isEditablePayout();
    }
}
