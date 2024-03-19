<?php

namespace App\Http\Requests\Api\Platform\Share;

use App\Http\Requests\BaseFormRequest;

class ShareSmsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[][]
     *
     * @psalm-return array{phone: list{'required', 'numeric', 'digits_between:8,15'}}
     */
    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'numeric',
                'digits_between:8,15',
            ],
        ];
    }
}
