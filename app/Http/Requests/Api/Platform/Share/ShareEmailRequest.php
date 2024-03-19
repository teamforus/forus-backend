<?php

namespace App\Http\Requests\Api\Platform\Share;

use App\Http\Requests\BaseFormRequest;

class ShareEmailRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return (mixed|string)[][]
     *
     * @psalm-return array{email: array{0: 'required'|mixed,...}}
     */
    public function rules(): array
    {
        return [
            'email' => [
                "required",
                ...$this->emailRules(),
            ],
        ];
    }
}
