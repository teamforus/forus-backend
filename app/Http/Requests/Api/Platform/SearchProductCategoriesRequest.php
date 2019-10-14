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
        $parent_id = $this->input('parent_id');

        return [
            'q' => 'string',
            'parent_id' => [
                'nullable',
                $parent_id == 'null' ? '' : 'exists:product_categories,id'
            ],
            'service' => 'boolean',
            'used' => 'boolean',
        ];
    }
}
