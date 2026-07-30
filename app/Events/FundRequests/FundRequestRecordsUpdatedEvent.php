<?php

namespace App\Events\FundRequests;

use App\Models\FundRequest;

class FundRequestRecordsUpdatedEvent extends BaseFundRequestEvent
{
    /**
     * @param FundRequest $fundRequest
     * @param string $mode
     * @param array $added
     */
    public function __construct(
        protected FundRequest $fundRequest,
        protected string $mode,
        protected array $added,
    ) {
        parent::__construct($fundRequest);
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
}
