<?php

namespace App\Services\BankService\Values;

class BankMonetaryAccount
{
    protected string $id;
    protected string $iban;
    protected string $name;

    /**
     * @param string $id
     * @param string $iban
     * @param string $name
     */
    public function __construct(
        string $id,
        string $iban,
        string $name
    ) {
        $this->id = $id;
        $this->iban = $iban;
        $this->name = $name;
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
    public function getIban(): string
    {
        return $this->iban;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}