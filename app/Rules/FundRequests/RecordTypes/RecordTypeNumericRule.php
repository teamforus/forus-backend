<?php

namespace App\Rules\FundRequests\RecordTypes;

class RecordTypeNumericRule extends BaseRecordTypeRule
{
    /**
     * @return (\Illuminate\Validation\Rules\In|string)[]
     *
     * @psalm-return array{0?: string, 1: 'numeric', 2?: string, 3?: string, 4?: \Illuminate\Validation\Rules\In|string}
     */
    public function rules(): array
    {
        return array_filter([
            $this->isRequiredRule(),
            "numeric",
            is_numeric($this->criterion->min) ? "min:{$this->criterion->min}" : null,
            is_numeric($this->criterion->max) ? "max:{$this->criterion->max}" : null,
            $this->getLengthRule(),
        ]);
    }
}
