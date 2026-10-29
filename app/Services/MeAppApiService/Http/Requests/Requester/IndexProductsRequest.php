<?php

namespace App\Services\MeAppApiService\Http\Requests\Requester;

use App\Http\Requests\BaseFormRequest;

class IndexProductsRequest extends BaseFormRequest
{
    /**
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'q' => $this->qRule(),
            'product_category_ids' => 'nullable|array|max:50',
            'product_category_ids.*' => 'nullable|integer|exists:product_categories,id',
            'fund_ids' => 'nullable|array|max:50',
            'fund_ids.*' => 'nullable|integer|exists:funds,id',
            'organization_id' => 'nullable|integer|exists:organizations,id',
            'from' => 'nullable|integer|max:10000',
            'to' => 'nullable|integer|max:10000',
            'postcode' => 'nullable|string|max:100',
            'distance' => 'nullable|integer|max:1000',
            'qr' => 'nullable|boolean',
            'reservation' => 'nullable|boolean',
            'extra_payment' => 'nullable|boolean',
            'regular' => 'nullable|boolean',
            'discount_fixed' => 'nullable|boolean',
            'discount_percentage' => 'nullable|boolean',
            'free' => 'nullable|boolean',
            'informational' => 'nullable|boolean',
            'bookmarked' => 'nullable|boolean',
            'order_by' => 'nullable|in:created_at,price,most_popular,name,randomized',
            'order_dir' => 'nullable|in:asc,desc',
            'page' => $this->pageRule(),
            'per_page' => $this->perPageRule(1000),
        ];
    }
}
