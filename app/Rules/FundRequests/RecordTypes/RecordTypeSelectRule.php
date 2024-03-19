<?php

namespace App\Rules\FundRequests\RecordTypes;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class RecordTypeSelectRule extends BaseRecordTypeRule
{
    /**
     * @return (\Illuminate\Validation\Rules\In|string)[]
     *
     * @psalm-return array{0?: string, 1: \Illuminate\Validation\Rules\In, 2: \Illuminate\Validation\Rules\In}
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
