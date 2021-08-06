<?php

namespace App\Http\Requests\DigID;

use App\Http\Requests\BaseFormRequest;

/**
 * Class ResolveDigIdRequest
 * @package App\Http\Requests\DigID
 */
class ResolveDigIdRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }
}
