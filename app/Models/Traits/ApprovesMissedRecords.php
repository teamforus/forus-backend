<?php

namespace App\Models\Traits;

use App\Models\Employee;

trait ApprovesMissedRecords
{
    /**
     * @param Employee|null $employee
     * @param string|null $note
     * @return void
     */
    public function approveMissedRecords(?Employee $employee, ?string $note): void
    {
        $this->update(['missing_records_approved' => true]);

        $noteText = trans('fund_request.missed_records_approved');

        if ($note) {
            $noteText .= "\n\n" . $note;
        }

        $this->addNote($noteText, $employee);
    }
}
