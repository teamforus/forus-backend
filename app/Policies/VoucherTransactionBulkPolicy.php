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
