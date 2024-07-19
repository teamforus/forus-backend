<?php

namespace App\Rules\FundRequests\CriterionRules;

use App\Helpers\Arr;
use Illuminate\Validation\Rule;

class CriteriaRuleTypeBoolRule extends BaseCriteriaRuleTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            Rule::in(Arr::pluck($this->recordType->getOptions(), 'value')),
            match($this->rule->operator) {
                '=' => Rule::in($this->rule->value),
                '!=' => Rule::notIn($this->rule->value),
                default => Rule::in([]),
            },
        ]);
    }
}
