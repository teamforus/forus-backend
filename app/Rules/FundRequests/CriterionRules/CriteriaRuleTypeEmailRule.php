<?php

namespace App\Rules\FundRequests\CriterionRules;

use Illuminate\Validation\Rule;

class CriteriaRuleTypeEmailRule extends BaseCriteriaRuleTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            'email',
            match($this->rule->operator) {
                '=' => Rule::in($this->rule->value),
                '!=' => Rule::notIn($this->rule->value),
                default => Rule::in([]),
            },
        ]);
    }
}
