<?php

namespace App\Events\FundRequests;

use App\Models\FundRequest;
use App\Models\PhysicalCardRequest;

class FundRequestPhysicalCardRequestEvent extends BaseFundRequestEvent
{
    public function __construct(
        FundRequest $fundRequest,
        protected PhysicalCardRequest $cardRequest,
    ) {
        parent::__construct($fundRequest);
    }
}
