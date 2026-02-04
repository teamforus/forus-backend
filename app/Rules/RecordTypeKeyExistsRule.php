<?php

namespace App\Rules;

use App\Models\RecordType;
use App\Searches\RecordTypeSearch;

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
        $search = new RecordTypeSearch([
            'without_system' => !$allowSystemKeys,
        ], RecordType::query());

        $this->recordTypes = $search->query()->pluck('key')->toArray();
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
        return in_array($value, $this->recordTypes) || $this->reject(__('validation.exists', [
            'attribute' => $attribute,
        ]));
    }
}
