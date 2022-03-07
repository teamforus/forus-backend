<?php

namespace App\Http\Requests\Api\Platform;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;

/**
 * Class SearchRequest
 * @package App\Http\Requests\Api\Platform
 */
class SearchRequest extends BaseFormRequest
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
            'q'                     => 'nullable|string|max:100',
            'per_page'              => 'nullable|numeric|max:1000',
            'fund_id'               => 'nullable|exists:funds,id',
            'product_category_id'   => 'nullable|exists:product_categories,id',
            'fund_type'             => 'nullable|in:' . implode(',', Fund::TYPES),
            'organization_id'       => 'nullable|exists:organizations,id',
            'search_item_types'     => 'nullable|array',
            'search_item_types.*'   => 'required|in:funds,providers,products',
            'overview'              => 'nullable|bool',
            'order_by'              => 'nullable|in:created_at',
            'order_by_dir'          => 'nullable|in:asc,desc',
            'postcode'              => 'nullable|string|max:100',
            'distance'              => 'nullable|integer|max:1000',
        ];
    }
}
