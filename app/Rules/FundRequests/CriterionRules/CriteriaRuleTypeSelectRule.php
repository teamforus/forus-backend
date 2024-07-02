<?php

namespace App\Rules\FundRequests\CriterionRules;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class CriteriaRuleTypeSelectRule extends BaseCriteriaRuleTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            Rule::in(Arr::pluck($this->rule->record_type->getOptions(), 'value')),
            match($this->rule->operator) {
                '=' => Rule::in($this->rule->value),
                '!=' => Rule::notIn($this->rule->value),
                default => Rule::in([]),
            },
        ]);
    }
}
