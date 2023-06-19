<?php

namespace App\Events\FundRequestRecords;

use App\Events\FundRequests\BaseFundRequestEvent;
use App\Models\Employee;
use App\Models\FundRequestRecord;

abstract class BaseFundRequestRecordEvent extends BaseFundRequestEvent
{
    protected FundRequestRecord $fundRequestRecord;
    protected bool $notifyRequester;

    /**
     * Create a new event instance.
     *
     * @param FundRequestRecord $fundRequestRecord
     * @param Employee|null $employee
     * @param Employee|null $supervisorEmployee
     * @param bool $notifyRequester
     */
    public function __construct(
        FundRequestRecord $fundRequestRecord,
        Employee $employee = null,
        ?Employee $supervisorEmployee = null,
        bool $notifyRequester = true,
    ) {
        parent::__construct($fundRequestRecord->fund_request, $employee, $supervisorEmployee);
        $this->fundRequestRecord = $fundRequestRecord;
        $this->notifyRequester = $notifyRequester;
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

    /**
     * Get notify requester setting
     *
     * @return bool
     */
    public function getNotifyRequester(): bool
    {
        return $this->notifyRequester;
    }
}
