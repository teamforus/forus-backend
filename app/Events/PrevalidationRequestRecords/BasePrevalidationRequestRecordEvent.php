<?php

namespace App\Events\PrevalidationRequestRecords;

use App\Models\Employee;
use App\Models\PrevalidationRequestRecord;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class BasePrevalidationRequestRecordEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param PrevalidationRequestRecord $record
     * @param Employee|null $employee
     */
    public function __construct(
        protected PrevalidationRequestRecord $record,
        protected ?Employee $employee = null,
    ) {
    }

    /**
     * Get the fund request.
     *
     * @return PrevalidationRequestRecord
     */
    public function getPrevalidationRequestRecord(): PrevalidationRequestRecord
    {
        return $this->record;
    }

    /**
     * @return Employee|null
     */
    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }
}
