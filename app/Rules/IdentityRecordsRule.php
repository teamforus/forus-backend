<?php

namespace App\Rules;

use App\Models\RecordType;
use Illuminate\Contracts\Validation\Rule;

/**
 * Class IdentityRecordsRule
 * @package App\Rules
 */
class IdentityRecordsRule implements Rule
{
    private string $message;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (!is_array($value)) {
            $this->message = trans('validation.array');
            return false;
        }

        $invalidKeys = array_diff(array_keys($value), RecordType::search(false)->pluck('key')->toArray());

        if (!empty($invalidKeys)) {
            $this->message = trans('validation.unknown_record_key', [
                'key' => $invalidKeys[0],
            ]);

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }
}
