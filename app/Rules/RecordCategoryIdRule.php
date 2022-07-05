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
        $recordRepo = resolve('forus.services.record');
        $request = BaseFormRequest::createFromBase(request());

        return !empty($recordRepo->categoryGet($request->auth_address(), $value));
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
