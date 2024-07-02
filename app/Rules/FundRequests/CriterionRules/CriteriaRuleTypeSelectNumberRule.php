<?php

namespace App\Rules\FundRequests\CriterionRules;

use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class CriteriaRuleTypeSelectNumberRule extends BaseCriteriaRuleTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            'numeric',
            Rule::in(Arr::pluck($this->recordType->getOptions(), 'value')),
            match($this->rule->operator) {
                '=' => Rule::in($this->rule->value),
                '!=' => Rule::notIn($this->rule->value),
                '>' => "gt:{$this->rule->value}",
                '<' => "lt:{$this->rule->value}",
                '>=' => "gte:{$this->rule->value}",
                '<=' => "lte:{$this->rule->value}",
                default => Rule::in([]),
            },
        ]);
    }
}