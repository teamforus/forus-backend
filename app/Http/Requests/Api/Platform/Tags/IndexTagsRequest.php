<?php

namespace App\Http\Requests\Api\Platform\Tags;

use App\Http\Requests\BaseFormRequest;

class IndexTagsRequest extends BaseFormRequest
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
            'type' => 'nullable|in:funds',
            'scope' => 'nullable|string:in,dashboard,webshop',
            'per_page' => 'nullable|numeric|min:1|max:1000',
        ];
    }
}
