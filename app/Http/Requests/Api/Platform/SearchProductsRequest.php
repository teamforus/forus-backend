<?php

namespace App\Http\Requests\Api\Platform;

use App\Http\Requests\BaseFormRequest;
use App\Models\Product;

class SearchProductsRequest extends BaseFormRequest
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
            'q' => 'nullable|string|max:100',
            'unlimited_stock' => 'nullable|boolean',
            'perice_type' => 'nullable|string|in:' . implode(',', Product::PRICE_TYPES),
            'show_all' => 'nullable|boolean',
            'per_page' => 'nullable|numeric|max:1000',
            'fund_id' => 'nullable|exists:funds,id',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'order_by' => 'nullable|in:name,created_at,price_min,price_max,price,most_popular,randomized',
            'order_dir' => 'nullable|in:asc,desc',
            'simplified' => 'nullable|bool',
            'postcode' => 'nullable|string|max:100',
            'distance' => 'nullable|integer|max:1000',
            'from' => 'nullable|integer|max:10000',
            'to' => 'nullable|integer|max:10000',
            'qr' => 'nullable|bool',
            'reservation' => 'nullable|bool',
            'extra_payment' => 'nullable|bool',
            'bookmarked' => 'nullable|bool',
        ];
    }
}
