<?php

namespace App\Services\BankService\Contracts;

abstract class BankContract
{
    abstract public function makeAuthorizationUrl(): string;
}