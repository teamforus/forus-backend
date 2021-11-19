<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\VoucherTransactionBulk;
use Illuminate\Auth\Access\HandlesAuthorization;

class VoucherTransactionBulkPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any voucher transaction bulks.
     *
     * @param string $identity_address
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(string $identity_address, Organization $organization): bool
    {
        return $organization->identityCan($identity_address, 'view_finances');
    }

    /**
     * Determine whether the user can view the voucher transaction bulk.
     *
     * @param string $identity_address
     * @param VoucherTransactionBulk $voucherTransactionBulk
     * @param Organization $organization
     * @return bool
     */
    public function show(
        string $identity_address,
        VoucherTransactionBulk $voucherTransactionBulk,
        Organization $organization
    ): bool {
        return
            $this->checkIntegrity($voucherTransactionBulk, $organization) &&
            $organization->identityCan($identity_address, 'view_finances');
    }

    /**
     * Determine whether the user can build a new voucher transaction bulk.
     *
     * @param string $identity_address
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function store(
        string $identity_address,
        Organization $organization
    ) {
        $hasPermission = $organization->identityCan($identity_address, 'manage_transaction_bulks');

        if ($hasPermission) {
            if (VoucherTransactionBulk::getNextBulkTransactionsForSponsor($organization)->exists()) {
                return true;
            }

            return $this->deny(implode("", [
                "Action unavailable, you need at least one pending",
                "transaction to create a new bulk.",
            ]), 403);
        }

        return false;
    }

    /**
     * Determine whether the user can build a new voucher transaction bulk.
     *
     * @param string $identity_address
     * @param VoucherTransactionBulk $voucherTransactionBulk
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function resetBulk(
        string $identity_address,
        VoucherTransactionBulk $voucherTransactionBulk,
        Organization $organization
    ) {
        $hasPermission =
            $this->checkIntegrity($voucherTransactionBulk, $organization) &&
            $organization->identityCan($identity_address, 'manage_transaction_bulks');

        if (!$voucherTransactionBulk->isRejected()) {
            return $this->deny("Only rejected bulks can be resent to the bank.");
        }

        return $hasPermission;
    }

    /**
     * Determine whether the user can build a new voucher transaction bulk.
     *
     * @param string $identity_address
     * @param VoucherTransactionBulk $voucherTransactionBulk
     * @param Organization $organization
     * @return bool|\Illuminate\Auth\Access\Response
     */
    public function setAcceptedManually(
        string $identity_address,
        VoucherTransactionBulk $voucherTransactionBulk,
        Organization $organization
    ) {
        $hasPermission =
            $this->checkIntegrity($voucherTransactionBulk, $organization) &&
            $organization->identityCan($identity_address, 'manage_transaction_bulks');

        if (!$voucherTransactionBulk->isPending()) {
            return $this->deny("Only pending bulks can be approved manually.");
        }

        return $hasPermission;
    }

    /**
     * Check that voucher transaction bulk belongs to given organization
     * @param VoucherTransactionBulk $voucherTransactionBulk
     * @param Organization $organization
     * @return bool
     */
    protected function checkIntegrity(
        VoucherTransactionBulk $voucherTransactionBulk,
        Organization $organization
    ): bool {
        return $voucherTransactionBulk->bank_connection->organization_id == $organization->id;
    }
}
