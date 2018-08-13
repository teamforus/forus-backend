<?php

namespace App\Http\Requests\Api\Platform\Validator\ValidatorRequest;

use Illuminate\Foundation\Http\FormRequest;

class ValidateValidatorRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'state' => 'required|in:approved,declined'
        ];
    }
}
