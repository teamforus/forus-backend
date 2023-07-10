<?php

namespace App\Services\DigIdService\Objects;

class ClientTls
{
    /**
     * @param string $key
     * @param string $cert
     */
    public function __construct(
        protected string $key,
        protected string $cert,
    ) {}

    /**
     * @return string
     */
    public function getCert(): string
    {
        return $this->cert;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }
}