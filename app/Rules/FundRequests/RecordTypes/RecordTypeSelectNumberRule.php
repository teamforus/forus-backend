<?php

namespace App\Rules\FundRequests\RecordTypes;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class RecordTypeSelectNumberRule extends BaseRecordTypeRule
{
    /**
     * @return (\Illuminate\Validation\Rules\In|string)[]
     *
     * @psalm-return array{0?: string, 1: 'numeric', 2: \Illuminate\Validation\Rules\In, 3?: \Illuminate\Validation\Rules\In|string}
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
