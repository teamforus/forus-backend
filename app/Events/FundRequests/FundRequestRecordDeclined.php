<?php

namespace App\Events\FundRequests;

use App\Models\FundRequest;
use App\Models\FundRequestRecord;

class FundRequestRecordDeclined extends BaseFundRequestEvent
{
    protected $fundRequestRecord;

    /**
     * Create a new event instance.
     *
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $fundRequestRecord
     */
    public function __construct(FundRequest $fundRequest, FundRequestRecord $fundRequestRecord)
    {
        parent::__construct($fundRequest);
        $this->fundRequestRecord = $fundRequestRecord;
    }

    /**
     * Get the fund request
     *
     * @return FundRequestRecord
     */
    public function getFundRequestRecord(): FundRequestRecord
    {
        return $this->fundRequestRecord;
    }
}
