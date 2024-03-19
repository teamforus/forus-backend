<?php

namespace App\Http\Requests\Api\Identity;

use App\Http\Requests\BaseFormRequest;

class IdentityDestroyRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{comment: 'nullable|string'}
     */
    public function rules(): array
    {
        return [
            'comment' => 'nullable|string'
        ];
    }
}
