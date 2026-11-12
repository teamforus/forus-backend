<?php

namespace App\Modules\DCP\Data;

use Carbon\CarbonImmutable;

readonly class DCPResponseData
{
    /**
     * @param int|null $httpStatus
     * @param string|null $orderId
     * @param string|null $remoteId
     * @param int|null $statusId
     * @param string|null $message
     * @param CarbonImmutable|null $shippedAt
     * @param string|null $correlationId
     * @param string[] $errors
     * @param array|null $body
     */
    public function __construct(
        public ?int $httpStatus = null,
        public ?string $orderId = null,
        public ?string $remoteId = null,
        public ?int $statusId = null,
        public ?string $message = null,
        public ?CarbonImmutable $shippedAt = null,
        public ?string $correlationId = null,
        public array $errors = [],
        public ?array $body = null,
    ) {
    }

    /**
     * @return array{http_status: int|null, body: array|null}
     */
    public function toArray(): array
    {
        return [
            'http_status' => $this->httpStatus,
            'body' => $this->body,
        ];
    }
}
