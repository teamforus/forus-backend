<?php

namespace App\Rules\FundRequests\RecordTypes;

class RecordTypeStringRule extends BaseRecordTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            $this->isRequiredRule(),
            "string",
            is_numeric($this->criterion->min) ? "min:{$this->criterion->min}" : null,
            is_numeric($this->criterion->max) ? "max:{$this->criterion->max}" : null,
            $this->getLengthRule(),
        ]);
    }
}
