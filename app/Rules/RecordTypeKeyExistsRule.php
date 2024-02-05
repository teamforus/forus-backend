<?php

namespace App\Rules;

use App\Models\RecordType;

class RecordTypeKeyExistsRule extends BaseRule
{
    protected array $recordTypes;

    /**
     * Create a new rule instance.
     *
     * @param bool $allowSystemKeys
     */
    public function __construct(bool $allowSystemKeys = false)
    {
        $this->recordTypes = RecordType::search($allowSystemKeys)->pluck('key')->toArray();
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
        return in_array($value, $this->recordTypes) || $this->reject(trans('validation.exists', [
            'attribute' => $attribute,
        ]));
    }
}
