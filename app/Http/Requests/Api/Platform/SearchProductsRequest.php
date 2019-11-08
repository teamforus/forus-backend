<?php

namespace App\Http\Requests\Api\Platform;

use Illuminate\Foundation\Http\FormRequest;

class SearchProductsRequest extends FormRequest
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
            'q'                     => 'nullable|string|max:100',
            'unlimited_stock'       => 'nullable|boolean',
            'per_page'              => 'nullable|numeric|max:1000',
            'fund_id'               => 'nullable|exists:funds,id',
            'product_category_id'   => 'nullable|exists:product_categories,id'
        ];
    }
}
