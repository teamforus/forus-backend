<?php

namespace App\Services\BankService\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class BankPolicy
{
    use HandlesAuthorization;

    /**
     * @param $identity_address
     * @return bool
     */
    public function viewAny($identity_address): bool
    {
        return !empty($identity_address);
    }

    /**
     * @param $identity_address
     * @return bool
     */
    public function show($identity_address): bool
    {
        return !empty($identity_address);
    }
}
