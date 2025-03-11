<?php

namespace App\Rules\FundRequests\CriterionRules;

use Illuminate\Validation\Rule;

class CriteriaRuleTypeNumericRule extends BaseCriteriaRuleTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            'numeric',
            match($this->rule->operator) {
                '=' => Rule::in([floatval($this->rule->value)]),
                '!=' => Rule::notIn([floatval($this->rule->value)]),
                '>' => "gt:{$this->rule->value}",
                '<' => "lt:{$this->rule->value}",
                '>=' => "gte:{$this->rule->value}",
                '<=' => "lte:{$this->rule->value}",
                default => Rule::in([]),
            },
        ]);
    }
}
