<?php

namespace App\Events\PrevalidationRequests;

use App\Models\PrevalidationRequest;

class PrevalidationRequestFailed extends BasePrevalidationRequestEvent
{
    protected string $reason;

    /**
     * @param PrevalidationRequest $prevalidationRequest
     * @param string $reason
     */
    public function __construct(PrevalidationRequest $prevalidationRequest, string $reason)
    {
        parent::__construct($prevalidationRequest);
        $this->reason = $reason;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
