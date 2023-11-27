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
        if (!$this->findCriterion($attribute)) {
            return $this->reject(trans('validation.in', compact('attribute')));
        }

        return  true;
    }
}
