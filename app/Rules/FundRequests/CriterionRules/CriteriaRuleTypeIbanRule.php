<?php

namespace App\Rules\FundRequests\CriterionRules;

use App\Rules\Base\IbanRule;
use Illuminate\Validation\Rule;

class CriteriaRuleTypeIbanRule extends BaseCriteriaRuleTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            new IbanRule(),
            match($this->rule->operator) {
                '=' => Rule::in($this->rule->value),
                '!=' => Rule::notIn($this->rule->value),
                default => Rule::in([]),
            },
        ]);
    }
}
