<?php

namespace App\Rules;

use App\Models\Fund;

class PrevalidationDataRule extends BaseRule
{
    /**
     * Create a new rule instance.
     *
     * @param Fund|null $fund
     * @param bool $exceptPrefillKeys
     */
    public function __construct(protected ?Fund $fund = null, protected bool $exceptPrefillKeys = false)
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $data = collect($value);

        if ($data->isEmpty()) {
            return $this->reject(__('validation.prevalidated_empty_data'));
        }

        $fund = $this->fund;

        foreach ($data as $records) {
            $requiredKeys = $fund ? $fund->requiredPrevalidationKeys(false, $records, $this->exceptPrefillKeys) : [];
            $records = collect($records);

            if ($fund && $records->keys()->search($fund->fund_config->csv_primary_key) === false) {
                return $this->reject(__('validation.prevalidation_missing_primary_key'));
            }

            if ($fund && $records->keys()->intersect($requiredKeys)->count() < count($requiredKeys)) {
                return $this->reject(__('validation.prevalidation_missing_required_keys'));
            }
        }

        return true;
    }
}
