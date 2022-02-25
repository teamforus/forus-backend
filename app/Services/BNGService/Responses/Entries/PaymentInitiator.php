<?php

namespace App\Services\BNGService\Responses\Entries;

class PaymentInitiator
{
    protected $name;

    /**
     * @param string|null $name
     */
    public function __construct(string $name = null)
    {
        $this->name = $name ?: env('BNG_PAYMENT_INITIATOR_NAME', 'Forus');
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}