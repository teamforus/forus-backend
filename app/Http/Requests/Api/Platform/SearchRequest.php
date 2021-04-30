<?php

namespace App\Http\Requests\Api\Platform;

use App\Models\Fund;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'q'                     => 'nullable|string|max:100',
            'per_page'              => 'nullable|numeric|max:1000',
            'fund_id'               => 'nullable|exists:funds,id',
            'product_category_id'   => 'nullable|exists:product_categories,id',
            'fund_type'             => 'nullable|in:' . implode(',', Fund::TYPES),
            'organization_id'       => 'nullable|exists:organizations,id',
            'order_by'              => 'nullable|in:created_at',
            'order_by_dir'          => 'nullable|in:asc,desc',
            'search_item_types'     => 'nullable|array',
            'overview'              => 'nullable',
        ];
    }
}
