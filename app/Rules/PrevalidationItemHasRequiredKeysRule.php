<?php

namespace App\Rules;

use App\Models\Fund;

class PrevalidationItemHasRequiredKeysRule extends BaseRule
{
    /**
     * @var ?Fund null
     */
    private ?Fund $fund;

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
        if ($this->fund || !is_array($value)) {
            return $this->reject(trans('validation.required'));
        }

        if (count(array_diff($this->requiredKeys($this->fund), array_keys($value))) !== 0) {
            return $this->reject(trans('validation.in'));
        }

        return true;
    }

    /**
     * @param Fund|null $fund
     * @return array
     */
    protected function requiredKeys(?Fund $fund): array
    {
        return $fund?->requiredPrevalidationKeys()->toArray() ?: [];
    }
}
