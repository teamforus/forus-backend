<?php

namespace App\Policies;

use App\Http\Requests\BaseFormRequest;
use App\Models\Identity;
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
     * @param Identity $identity
     * @param Organization $organization
     * @return bool
     */
    public function viewAny(Identity $identity, Organization $organization): bool
    {
        return $organization->identityCan($identity, 'view_finances');
    }

    /**
     * Determine whether the user can view the voucher transaction bulk.
     *
     * @param Identity $identity
     * @param VoucherTransactionBulk $voucherTransactionBulk
     * @param Organization $organization
     * @return bool
     */
    public function show(
        Identity $identity,
        VoucherTransactionBulk $voucherTransactionBulk,
        Organization $organization
    ): bool {
        return
            $this->checkIntegrity($voucherTransactionBulk, $organization) &&
            $organization->identityCan($identity, 'view_finances');
    }

    /**
     * Determine whether the user can build a new voucher transaction bulk.
     *
     * @param Identity $identity
     * @param Organization $organization
     * @return Response|bool
     */
    public function store(Identity $identity, Organization $organization): Response|bool
    {
        $hasPermission = $organization->identityCan($identity, 'manage_transaction_bulks');

        if ($hasPermission) {
            if (VoucherTransactionBulk::getNextBulkTransactionsForSponsor(
                $organization,
                BaseFormRequest::createFromBase(request())
            )->exists()) {
                return true;
            }

            return $this->deny(implode(' ', [
                'Om een nieuwe bulktransactie aan te maken,',
                'moet er minstens één lopende transactie zijn.',
            ]), 403);
        }

        return false;
    }

    /**
     * Determine whether the user can build a new voucher transaction bulk.
     *
     * @param Identity $identity
     * @param VoucherTransactionBulk $bulk
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function resetBulk(
        Identity $identity,
        VoucherTransactionBulk $bulk,
        Organization $organization
    ): Response|bool {
        $integrityIsValid = $this->checkIntegrity($bulk, $organization);
        $hasPermission = $organization->identityCan($identity, 'manage_transaction_bulks');
        $bank = $bulk->bank_connection->bank;

        if ($bank->isBunq() && !$bulk->isRejected()) {
            return $this->deny('Only rejected bulks can be resent to the bank.');
        }

        if ($bank->isBunq() && (!$bulk->isPending() && !$bulk->isDraft() && !$bulk->isRejected())) {
            return $this->deny('Only pending, draft and rejected bulks can be resent to the bank.');
        }

        return $integrityIsValid && $hasPermission;
    }

    /**
     * Determine whether the user can manually accept bulk transaction.
     *
     * @param Identity $identity
     * @param VoucherTransactionBulk $voucherTransactionBulk
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function setAcceptedManually(
        Identity $identity,
        VoucherTransactionBulk $voucherTransactionBulk,
        Organization $organization
    ): Response|bool {
        $hasPermission =
            $this->checkIntegrity($voucherTransactionBulk, $organization) &&
            $organization->identityCan($identity, 'manage_transaction_bulks') &&
            $voucherTransactionBulk->is_exported;

        if (!$voucherTransactionBulk->isDraft()) {
            return $this->deny('Alleen concept bulk lijsten kunnen handmatig worden geaccepteerd.');
        }

        if (!$organization->allow_manual_bulk_processing) {
            return $this->deny('Handmatig accepteren is niet mogelijk.');
        }

        return $hasPermission && $voucherTransactionBulk->bank_connection->bank->isBNG();
    }

    /**
     * Determine whether the user can manually accept bulk transaction.
     *
     * @param Identity $identity
     * @param VoucherTransactionBulk $voucherTransactionBulk
     * @param Organization $organization
     * @return Response|bool
     * @noinspection PhpUnused
     */
    public function exportBulkToBNG(
        Identity $identity,
        VoucherTransactionBulk $voucherTransactionBulk,
        Organization $organization
    ): Response|bool {
        $hasPermission =
            $this->checkIntegrity($voucherTransactionBulk, $organization) &&
            $organization->identityCan($identity, 'manage_transaction_bulks');

        if (!$voucherTransactionBulk->isDraft()) {
            return $this->deny('Alleen concept bulk lijsten kunnen worden geëxporteerd.');
        }

        if (!$organization->allow_manual_bulk_processing) {
            return $this->deny('Het exporteren van het SEPA bestand is niet mogelijk.');
        }

        return $hasPermission && $voucherTransactionBulk->bank_connection->bank->isBNG();
    }

    /**
     * Check that voucher transaction bulk belongs to given organization.
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
