<?php

namespace App\Rules\FundRequests\RecordTypes;

class RecordTypeDateRule extends BaseRecordTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            $this->isRequiredRule(),
            "date",
            "date_format:$this->dateFormat",
            $this->isValidDate($this->criterion->min) ? "after_or_equal:{$this->criterion->min}" : null,
            $this->isValidDate($this->criterion->max) ? "before_or_equal:{$this->criterion->max}" : null,
            match($this->criterion->operator) {
                '=' => "date_equals:{$this->criterion->value}",
                '>' => "after:{$this->criterion->value}",
                '<' => "before:{$this->criterion->value}",
                '>=' => "after_or_equal:{$this->criterion->value}",
                '<=' => "before_or_equal:{$this->criterion->value}",
                '*' => null,
                default => [],
            },
        ]);
    }
}
