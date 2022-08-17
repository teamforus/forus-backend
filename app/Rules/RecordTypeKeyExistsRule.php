<?php

namespace App\Rules;

use App\Models\RecordType;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Eloquent\Collection;

class RecordTypeKeyExistsRule implements Rule
{
    protected Collection $recordTypes;

    /**
     * Create a new rule instance.
     *
     * @param bool $allowSystemKeys
     */
    public function __construct(bool $allowSystemKeys = false)
    {
        $this->recordTypes = RecordType::search($allowSystemKeys);
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
        return (bool) $this->recordTypes->first(fn(RecordType $type) => $type->key === $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return trans('validation.exists');
    }
}
