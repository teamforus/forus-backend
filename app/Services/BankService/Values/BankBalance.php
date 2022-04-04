<?php

namespace App\Services\BankService\Values;

class BankBalance
{
    protected $amount;
    protected $currency;

    /**
     * @param string $amount
     * @param string $currency
     */
    public function __construct(string $amount, string $currency = "EUR")
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }
}