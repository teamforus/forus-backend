<?php

namespace App\Rules\FundRequests\RecordTypes;

use Illuminate\Validation\Rule;

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
            match($this->criterion->operator) {
                '=' => Rule::in([$this->criterion->value]),
                '>' => is_numeric($this->criterion->value) ? ("gt:{$this->criterion->value}") : 'in:',
                '<' => is_numeric($this->criterion->value) ? ("lt:{$this->criterion->value}") : 'in:',
                '*' => null,
                default => Rule::in([]),
            },
        ]);
    }
}
