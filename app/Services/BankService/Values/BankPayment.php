<?php

namespace App\Services\BankService\Values;

class BankPayment
{
    protected string $id;
    protected string $amount;
    protected string $currency;
    protected string $description;

    /**
     * @param string $id
     * @param string $amount
     * @param string $currency
     * @param string $description
     */
    public function __construct(
        string $id,
        string $amount,
        string $currency = "EUR",
        string $description = ""
    ) {
        $this->id = $id;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }
}