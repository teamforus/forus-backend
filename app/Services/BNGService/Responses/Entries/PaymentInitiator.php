<?php

namespace App\Services\BNGService\Responses\Entries;

class PaymentInitiator
{
    protected $name;

    /**
     * @param string|null $name
     */
    public function __construct(?string $name = null)
    {
        $this->name = $name ?: 'Payment initiator';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}