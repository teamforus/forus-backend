<?php

namespace App\Http\Requests\Api\Platform;

use App\Http\Requests\BaseFormRequest;

class RegisterDevicePushRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{id: 'required|string|min:8'}
     */
    public function rules(): array
    {
        return [
            'id' => 'required|string|min:8'
        ];
    }
}
