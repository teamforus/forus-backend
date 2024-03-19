<?php

namespace App\Http\Requests\Api\Platform;

use App\Http\Requests\BaseFormRequest;

class FormatRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{markdown: 'nullable|string|max:10000'}
     */
    public function rules(): array
    {
        return [
            'markdown' => 'nullable|string|max:10000',
        ];
    }
}
