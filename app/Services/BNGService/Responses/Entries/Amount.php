<?php

namespace App\Services\BNGService\Responses\Entries;

class Amount
{
    protected string $amount;
    protected string $currency;

    /**
     * @param string $amount
     * @param string $currency
     */
    public function __construct(string $amount, string $currency = 'EUR')
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

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        return [
            'currency' => $this->getCurrency(),
            'amount' => $this->getAmount(),
        ];
    }
}