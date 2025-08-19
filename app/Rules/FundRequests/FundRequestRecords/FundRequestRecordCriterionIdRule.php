<?php

namespace App\Rules\FundRequests\FundRequestRecords;

use App\Rules\FundRequests\BaseFundRequestRule;

class FundRequestRecordCriterionIdRule extends BaseFundRequestRule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, mixed $value): bool
    {
        if (!is_numeric($value)) {
            return $this->reject(__('validation.numeric', compact('attribute')));
        }

        if (!$this->fund->criteria->firstWhere('id', $value)) {
            return $this->reject(__('validation.in', compact('attribute')));
        }

        return  true;
    }
}
