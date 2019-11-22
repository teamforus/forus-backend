<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use Illuminate\Foundation\Http\FormRequest;

class IndexProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'q' => 'nullable|string',
            'per_page' => 'nullable|numeric|max:100',
        ];
    }
}
