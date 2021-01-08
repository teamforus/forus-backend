<?php

namespace App\Http\Requests\Api\Platform;

use App\Models\Fund;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class SearchProductsRequest
 * @package App\Http\Requests\Api\Platform
 */
class SearchProductsRequest extends FormRequest
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
            'unlimited_stock'       => 'nullable|boolean',
            'perice_type'           => 'nullable|string|in:' . join(',', Product::PRICE_TYPES),
            'show_all'              => 'nullable|boolean',
            'per_page'              => 'nullable|numeric|max:1000',
            'fund_id'               => 'nullable|exists:funds,id',
            'product_category_id'   => 'nullable|exists:product_categories,id',
            'fund_type'             => 'nullable|in:' . implode(',', Fund::TYPES),
        ];
    }
}
