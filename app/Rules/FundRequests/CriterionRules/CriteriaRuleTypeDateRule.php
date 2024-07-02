<?php

namespace App\Rules\FundRequests\CriterionRules;

use Illuminate\Validation\Rule;

class CriteriaRuleTypeDateRule extends BaseCriteriaRuleTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            "date",
            "date_format:$this->dateFormat",
            match($this->rule->operator) {
                '=' => "date_equals:{$this->rule->value}",
                '=!' => Rule::notIn($this->rule->value),
                '>' => "after:{$this->rule->value}",
                '<' => "before:{$this->rule->value}",
                '>=' => "after_or_equal:{$this->rule->value}",
                '<=' => "before_or_equal:{$this->rule->value}",
                default => Rule::in([]),
            },
        ]);
    }
}
