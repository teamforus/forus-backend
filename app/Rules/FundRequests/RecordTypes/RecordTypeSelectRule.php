<?php

namespace App\Rules\FundRequests\RecordTypes;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class RecordTypeSelectRule extends BaseRecordTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            $this->isRequiredRule(),
            Rule::in(Arr::pluck($this->criterion->record_type->getOptions(), 'value')),
            match($this->criterion->operator) {
                '=' => Rule::in([$this->criterion->value]),
                '*' => Rule::in(Arr::pluck($this->criterion->record_type->getOptions(), 'value')),
                default => Rule::in([]),
            },
        ]);
    }
}
