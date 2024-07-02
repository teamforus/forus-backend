<?php

namespace App\Rules\FundRequests\CriterionRules;

use Illuminate\Validation\Rule;

class CriteriaRuleTypeStringRule extends BaseCriteriaRuleTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            "string",
            match($this->rule->operator) {
                '=' => Rule::in($this->rule->value),
                '!=' => Rule::notIn($this->rule->value),
                default => Rule::in([]),
            },
        ]);
    }
}
