<?php

namespace App\Http\Requests\Api\Platform;

use App\Http\Requests\BaseFormRequest;

class SearchBusinessTypesRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{q: 'string|min:1|max:10000', used: 'boolean', per_page: 'nullable|numeric|min:1|max:10000'}
     */
    public function rules(): array
    {
        return [
            'q' => 'string|min:1|max:10000',
            'used' => 'boolean',
            'per_page' => 'nullable|numeric|min:1|max:10000',
        ];
    }
}
