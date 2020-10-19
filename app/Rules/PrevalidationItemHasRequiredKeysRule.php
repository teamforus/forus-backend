<?php

namespace App\Rules;

use App\Models\Fund;

class PrevalidationItemHasRequiredKeysRule extends BaseRule
{
    /**
     * @var ?Fund null
     */
    private $fund;

    /**
     * PrevalidationItemHasRequiredKeysRule constructor.
     * @param Fund|null $fund
     */
    public function __construct(?Fund $fund = null)
    {
        $this->fund = $fund;
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
        $required_keys = $this->fund->requiredPrevalidationKeys()->toArray();

        if (!($this->fund && is_array($value))) {
            return $this->rejectWithMessage(trans('validation.required'));
        }

        if (count(array_diff($required_keys, array_keys($value))) !== 0) {
            return $this->rejectWithMessage(trans('validation.in'));
        }

        return true;
    }
}
