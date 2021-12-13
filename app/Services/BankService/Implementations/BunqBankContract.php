<?php

namespace App\Services\BankService\Implementations;

use App\Services\BankService\Contracts\BankContract;

class BunqBankContract extends BankContract
{
    public function makeAuthorizationUrl(): string
    {
        return "";
    }
}