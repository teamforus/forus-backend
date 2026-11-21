<?php

namespace App\Services\DigIdService\Objects;

readonly class DigIdStartContext
{
    /**
     * @param string $callbackUrl
     * @param string|null $sessionSecret
     * @param ClientTls|null $tlsCert
     * @param string|null $requestId
     */
    public function __construct(
        public string $callbackUrl,
        public ?string $sessionSecret = null,
        public ?ClientTls $tlsCert = null,
        public ?string $requestId = null,
    ) {
    }
}
