<?php

namespace App\Events\FundRequestRecords;

use App\Models\Employee;
use App\Models\FundRequestRecord;

class FundRequestRecordUpdated extends BaseFundRequestRecordEvent
{
    protected string $previousValue;

    /**
     * @param FundRequestRecord $fundRequestRecord
     * @param Employee|null $employee
     * @param Employee|null $supervisorEmployee
     * @param string|null $previousValue
     */
    public function __construct(
        FundRequestRecord $fundRequestRecord,
        Employee $employee = null,
        ?Employee $supervisorEmployee = null,
        string $previousValue = null,
    ) {
        parent::__construct($fundRequestRecord, $employee, $supervisorEmployee);
        $this->previousValue = (string) $previousValue;
    }

    /**
     * @return string
     */
    public function getPreviousValue(): string
    {
        return $this->previousValue;
    }
}
