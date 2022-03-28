<?php

namespace App\Events\FundRequestRecords;

use App\Events\FundRequests\BaseFundRequestEvent;
use App\Models\Employee;
use App\Models\FundRequestRecord;

abstract class BaseFundRequestRecordEvent extends BaseFundRequestEvent
{
    protected FundRequestRecord $fundRequestRecord;

    /**
     * Create a new event instance.
     *
     * @param FundRequestRecord $fundRequestRecord
     * @param Employee|null $employee
     * @param Employee|null $supervisorEmployee
     */
    public function __construct(
        FundRequestRecord $fundRequestRecord,
        Employee $employee = null,
        ?Employee $supervisorEmployee = null
    ) {
        parent::__construct($fundRequestRecord->fund_request, $employee, $supervisorEmployee);
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
