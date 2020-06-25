<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use Illuminate\Foundation\Http\FormRequest;

class UpdateImplementationEmailRequest extends FormRequest
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
            'email_from_address'    => 'nullable|string|max:50',
            'email_from_name'       => 'nullable|string|max:50',
        ];
    }
}
