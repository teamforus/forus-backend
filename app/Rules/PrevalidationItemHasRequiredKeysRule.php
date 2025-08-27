<?php

namespace App\Rules;

use App\Models\Fund;

class PrevalidationItemHasRequiredKeysRule extends BaseRule
{
    /**
     * PrevalidationItemHasRequiredKeysRule constructor.
     * @param Fund $fund
     * @param array $recordValues
     */
    public function __construct(public Fund $fund, public array $recordValues = [])
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
        if (!$this->fund || !is_array($value)) {
            return $this->reject(__('validation.required'));
        }

        $requiredFields = $this->requiredKeys($this->fund);
        $requestFields = array_filter(array_keys($value), fn ($key) => $value[$key] ?: false);

        $missingFields = array_diff($requiredFields, $requestFields);
        $invalidFields = array_diff($requestFields, $requiredFields);

        if (!empty($invalidFields)) {
            return $this->reject(sprintf('Invalid fields: %s', implode(', ', $invalidFields)));
        }

        if (!empty($missingFields)) {
            return $this->reject(sprintf('Missing required fields: %s', implode(', ', $missingFields)));
        }

        return true;
    }

    /**
     * @param Fund|null $fund
     * @param bool $withOptional
     * @return array
     */
    protected function requiredKeys(?Fund $fund, bool $withOptional = false): array
    {
        return $fund?->requiredPrevalidationKeys($withOptional, $this->recordValues);
    }
}
