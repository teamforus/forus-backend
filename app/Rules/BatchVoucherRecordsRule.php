<?php

namespace App\Rules;

use App\Helpers\Arr;
use App\Models\RecordType;
use App\Models\VoucherRecord;
use Illuminate\Contracts\Validation\Rule;

class BatchVoucherRecordsRule implements Rule
{
    /**
     * @var string
     */
    protected string $message = 'Invalid voucher records.';

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (!is_null($value) && !is_array($value)) {
            return false;
        }

        $recordKeys = array_keys($value);
        $validKeys = RecordType::where('vouchers', true)->whereIn('key', $recordKeys)->pluck('key');

        $invalidKeys = array_merge(
            array_diff($validKeys->toArray(), $recordKeys),
            array_diff($recordKeys, $validKeys->toArray()),
        );

        // have invalid record keys
        if (!empty($invalidKeys)) {
            $this->message = "Invalid record types found: " . implode(', ', $invalidKeys) . ".";
            return false;
        }

        // only one record of the same type can exist at a time
        if (count(Arr::duplicates($recordKeys)) > 0) {
            $this->message = "Some records keys are duplicated";
            return false;
        }

        // find first validation error
        foreach ($recordKeys as $key) {
            if ($error = VoucherRecord::validateRecord($key, $value[$key])) {
                $this->message = $error;
                return false;
            }
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
