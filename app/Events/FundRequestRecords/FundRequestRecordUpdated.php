<?php

namespace App\Events\FundRequestRecords;

use App\Models\Employee;
use App\Models\FundRequestRecord;

class FundRequestRecordUpdated extends BaseFundRequestRecordEvent
{
    protected string $previousValue;

    /**
     * @return string
     */
    public function getPreviousValue(): string
    {
        return $this->previousValue;
    }
}
