<?php

namespace App\Http\Requests\BNG;

use App\Http\Requests\BaseFormRequest;

abstract class BaseBNGRedirectRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'state' => 'required|string',
        ];
    }
}
