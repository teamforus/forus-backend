<?php

namespace App\Services\BankService\Values;

class BankCreditorAccount
{
    protected string $iban;

    /**
     * @param string $iban
     */
    public function __construct(string $iban)
    {
        $this->iban = $iban;
    }

    /**
     * @return string
     */
    public function getIban(): string
    {
        return $this->iban;
    }
}