<?php

namespace App\Events\PrevalidationRequests;

use App\Models\PrevalidationRequest;

class PrevalidationRequestStateUpdated extends BasePrevalidationRequestEvent
{
    protected string $previous_state;

    /**
     * @param PrevalidationRequest $prevalidationRequest
     * @param string $previous_state
     */
    public function __construct(PrevalidationRequest $prevalidationRequest, string $previous_state)
    {
        parent::__construct($prevalidationRequest);
        $this->previous_state = $previous_state;
    }

    /**
     * @return string
     */
    public function getPreviousState(): string
    {
        return $this->previous_state;
    }
}
