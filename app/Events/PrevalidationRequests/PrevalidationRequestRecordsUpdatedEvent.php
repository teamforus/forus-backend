<?php

namespace App\Events\PrevalidationRequests;

use App\Models\PrevalidationRequest;

class PrevalidationRequestRecordsUpdatedEvent extends BasePrevalidationRequestEvent
{
    /**
     * @param PrevalidationRequest $prevalidationRequest
     * @param int $prevalidationId
     * @param string $prevalidationState
     * @param string $mode
     * @param array $added
     * @param array $updated
     * @param array $deleted
     */
    public function __construct(
        protected PrevalidationRequest $prevalidationRequest,
        protected int $prevalidationId,
        protected string $prevalidationState,
        protected string $mode,
        protected array $added,
        protected array $updated,
        protected array $deleted
    ) {
        parent::__construct($prevalidationRequest, null);
    }

    /**
     * @return int
     */
    public function getPrevalidationId(): int
    {
        return $this->prevalidationId;
    }

    /**
     * @return string
     */
    public function getPrevalidationState(): string
    {
        return $this->prevalidationState;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @return array
     */
    public function getAdded(): array
    {
        return $this->added;
    }

    /**
     * @return array
     */
    public function getUpdated(): array
    {
        return $this->updated;
    }

    /**
     * @return array
     */
    public function getDeleted(): array
    {
        return $this->deleted;
    }
}
