<?php

namespace App\Rules\FundRequests\RecordTypes;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class RecordTypeSelectNumberRule extends BaseRecordTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            $this->isRequiredRule(),
            'numeric',
            Rule::in(Arr::pluck($this->criterion->record_type->getOptions(), 'value')),
            match($this->criterion->operator) {
                '=' => Rule::in([$this->criterion->value]),
                '<=' => 'lte:' . intval($this->criterion->value),
                '>=' => 'gte:' . intval($this->criterion->value),
                '*' => Rule::in(Arr::pluck($this->criterion->record_type->getOptions(), 'value')),
                default => Rule::in([]),
            },
        ]);
    }
}
