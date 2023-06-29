<?php

namespace App\Rules\FundRequests;

class FundRequestRecordValueRule extends BaseFundRequestRule
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
        $criterion = $this->findCriterion($attribute);

        if (is_string($criterion)) {
            $this->msg = $criterion;
            return false;
        }

        $validator = $this->validateRecordValue($criterion, $attribute);

        if (!$validator->passes()) {
            $this->msg = array_first(array_first($validator->errors()->toArray()));
            return false;
        }

        return true;
    }
}
