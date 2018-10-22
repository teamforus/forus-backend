<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VoucherTransactionNote;
use Illuminate\Auth\Access\HandlesAuthorization;

class VoucherTransactionNotePolicy
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
    public function store(string $identity_address)
    {
        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @return bool
     */
    public function list(string $identity_address)
    {
        return !empty($identity_address);
    }

    /**
     * @param string $identity_address
     * @param VoucherTransactionNote $note
     * @return bool
     */
    public function show(
        string $identity_address,
        VoucherTransactionNote $note
    ) {
        return !empty($identity_address) && $note;
    }
}
