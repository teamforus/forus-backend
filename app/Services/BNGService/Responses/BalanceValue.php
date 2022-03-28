<?php

namespace App\Services\BNGService\Responses;

class BalanceValue extends Value
{
    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getBalanceType(): string
    {
        return $this->data['balanceType'];
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getBalanceAmount(): string
    {
        return $this->data['balanceAmount']['amount'];
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getBalanceCurrency(): string
    {
        return $this->data['balanceAmount']['currency'];
    }
}