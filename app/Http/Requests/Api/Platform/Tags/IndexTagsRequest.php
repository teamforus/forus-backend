<?php

namespace App\Http\Requests\Api\Platform\Tags;

use App\Http\Requests\BaseFormRequest;

class IndexTagsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{type: 'nullable|in:funds', scope: 'nullable|string:in,dashboard,webshop', per_page: 'nullable|numeric|min:1|max:1000'}
     */
    public function rules(): array
    {
        return [
            'type' => 'nullable|in:funds',
            'scope' => 'nullable|string:in,dashboard,webshop',
            'per_page' => 'nullable|numeric|min:1|max:1000',
        ];
    }
}
