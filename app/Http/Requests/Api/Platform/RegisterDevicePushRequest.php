<?php

namespace App\Http\Requests\Api\Platform;

use App\Http\Requests\BaseFormRequest;

class RegisterDevicePushRequest extends BaseFormRequest
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
        return [
            'id' => 'required|string|min:8',
        ];
    }
}
