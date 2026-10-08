<?php

namespace App\Services\DigIdService\Objects;

readonly class DigIdResolveContext
{
    /**
     * @param string $requestId
     * @param string|null $sessionSecret
     * @param ClientTls|null $tlsCert
     */
    public function __construct(
        public string $requestId,
        public ?string $sessionSecret = null,
        public ?ClientTls $tlsCert = null,
    ) {
    }
}
