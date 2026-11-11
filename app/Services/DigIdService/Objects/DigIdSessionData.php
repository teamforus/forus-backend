<?php

namespace App\Services\DigIdService\Objects;

use App\Services\DigIdService\Models\DigIdSession;
use InvalidArgumentException;

readonly class DigIdSessionData
{
    /**
     * @param int $implementationId
     * @param int $organizationId
     * @param string $connectionType
     * @param string $clientType
     * @param string|null $identityAddress
     * @param string $sessionRequest
     * @param string $sessionFinalUrl
     * @param string $browserChallenge
     * @param int|null $fundId
     * @param string|null $dvEntityId
     * @param string|null $serviceUuid
     */
    public function __construct(
        public int $implementationId,
        public int $organizationId,
        public string $connectionType,
        public string $clientType,
        public ?string $identityAddress,
        public string $sessionRequest,
        public string $sessionFinalUrl,
        public string $browserChallenge,
        public ?int $fundId = null,
        public ?string $dvEntityId = null,
        public ?string $serviceUuid = null,
    ) {
        if ($sessionRequest === DigIdSession::SESSION_REQUEST_FUND_REQUEST && $fundId === null) {
            throw new InvalidArgumentException('Fund verification requires a fund ID.');
        }

        if ($connectionType === DigIdSession::CONNECTION_TYPE_TVS && (!$dvEntityId || !$serviceUuid)) {
            throw new InvalidArgumentException('TVS sessions require an entity ID and service UUID.');
        }
    }
}
