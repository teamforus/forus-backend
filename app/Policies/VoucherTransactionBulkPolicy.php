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
