<?php

namespace App\Rules;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Contracts\Validation\Rule;

class RecordCategoryIdRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $request = BaseFormRequest::createFrom(request());
        $value = is_numeric($value) ? intval($value) : null;

        return $value && $request->identity()->record_categories()->where([
            'record_categories.id' => $value,
        ])->exists();
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
