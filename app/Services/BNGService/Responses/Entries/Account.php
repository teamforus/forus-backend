<?php

namespace App\Services\BNGService\Responses\Entries;

class Account
{
    protected $iban;
    protected $name = '';

    /**
     * @param string $iban
     * @param string $name
     */
    public function __construct(string $iban, string $name = '')
    {
        $this->iban = $iban;
        $this->name = $name;
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

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        return [
            'iban' => $this->getIban(),
            'name' => $this->getName(),
        ];
    }
}