<?php

namespace App\Rules\FundRequests;

class FundRequestRecordRecordTypeKeyRule extends BaseFundRequestRule
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
        $typesByKey = @collect(record_types_cached())->keyBy('key');

        if (is_string($criterion)) {
            $this->msg = $criterion;
            return false;
        }

        if ($criterion->record_type_key !== $value) {
            $this->msg = trans('validation.in', [
                'attribute' => $typesByKey[$value]['name']
            ]);

            return false;
        }

        return true;
    }
}
