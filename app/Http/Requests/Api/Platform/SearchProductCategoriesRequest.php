<?php

namespace App\Http\Requests\Api\Platform;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class SearchProductCategoriesRequest extends FormRequest
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
            'q' => 'string',
            'parent_id' => 'nullable',
            'service' => 'boolean',
            'used' => 'boolean',
        ];
    }
}
