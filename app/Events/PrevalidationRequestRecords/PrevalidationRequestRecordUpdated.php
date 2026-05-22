<?php

namespace App\Events\PrevalidationRequestRecords;

use App\Models\Employee;
use App\Models\PrevalidationRequestRecord;

class PrevalidationRequestRecordUpdated extends BasePrevalidationRequestRecordsEvent
{
    protected string $previousValue;

    /**
     * @param PrevalidationRequestRecord $record
     * @param Employee|null $employee
     * @param string|null $previousValue
     */
    public function __construct(
        PrevalidationRequestRecord $record,
        Employee $employee = null,
        string $previousValue = null,
    ) {
        parent::__construct($record, $employee);
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
