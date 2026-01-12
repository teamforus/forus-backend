<?php

namespace App\Events\PrevalidationRequests;

use App\Models\PrevalidationRequest;

class PrevalidationRequestStateUpdatedEvent extends BasePrevalidationRequestEvent
{
    /**
     * @param PrevalidationRequest $prevalidationRequest
     * @param array|null $responseData
     * @param string|null $previousState
     */
    public function __construct(
        protected PrevalidationRequest $prevalidationRequest,
        protected ?array $responseData,
        protected ?string $previousState
    ) {
        parent::__construct($prevalidationRequest, $responseData);
    }

    /**
     * @return string|null
     */
    public function getPreviousState(): ?string
    {
        return $this->previousState;
    }
}
