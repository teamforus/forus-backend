<?php

namespace App\Services\BankService\Implementations;

use App\Services\BankService\Contracts\BankContract;

class BunqBankContract extends BankContract
{
    /**
     * @return string
     *
     * @psalm-return ''
     */
    public function makeAuthorizationUrl(): string
    {
        return "";
    }
}