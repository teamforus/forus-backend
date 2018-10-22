<?php

namespace App\Policies;

use App\Models\User;
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

    public function showSponsor(
        string $identity_address,
        VoucherTransaction $transaction
    ) {
        return strcmp(
            $transaction->organization->identity_address,
            $identity_address
            ) == 0;
    }

    public function showProvider(
        string $identity_address,
        VoucherTransaction $transaction
    ) {
        return strcmp(
                $transaction->voucher->fund->organization->identity_address,
                $identity_address
            ) == 0;
    }
}
