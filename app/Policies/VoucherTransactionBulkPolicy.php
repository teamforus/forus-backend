<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\VoucherTransactionBulk;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

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
     * @return bool|Response
     */
    public function store(
        string $identity_address,
        Organization $organization
    ): Response|bool {
        $hasPermission = $organization->identityCan($identity_address, 'manage_transaction_bulks');

        if ($hasPermission) {
            if (VoucherTransactionBulk::getNextBulkTransactionsForSponsor($organization, request())->exists()) {
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
     * @param VoucherTransactionBulk $bulk
     * @param Organization $organization
     * @return bool|Response
     */
    public function resetBulk(
        string $identity_address,
        VoucherTransactionBulk $bulk,
        Organization $organization
    ): Response|bool {
        $integrityIsValid = $this->checkIntegrity($bulk, $organization);
        $hasPermission = $organization->identityCan($identity_address, 'manage_transaction_bulks');
        $bank = $bulk->bank_connection->bank;

        if ($bank->isBunq() && !$bulk->isRejected()) {
            return $this->deny("Only rejected bulks can be resent to the bank.");
        }

        if ($bank->isBunq() && (!$bulk->isPending() && !$bulk->isDraft() && !$bulk->isRejected())) {
            return $this->deny("Only pending, draft and rejected bulks can be resent to the bank.");
        }

        return $integrityIsValid && $hasPermission;
    }

    /**
     * Determine whether the user can build a new voucher transaction bulk.
     *
     * @param string $identity_address
     * @param VoucherTransactionBulk $voucherTransactionBulk
     * @param Organization $organization
     * @return bool|Response
     */
    public function setAcceptedManually(
        string $identity_address,
        VoucherTransactionBulk $voucherTransactionBulk,
        Organization $organization
    ): Response|bool {
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
     * @param VoucherTransactionBulk $bulk
     * @param Organization $organization
     * @return bool
     */
    protected function checkIntegrity(VoucherTransactionBulk $bulk, Organization $organization): bool
    {
        $bank = $bulk->bank_connection->bank;
        $validBank = $bank->isBNG() || $bank->isBunq();

        return $validBank && ($bulk->bank_connection->organization_id == $organization->id);
    }
}
