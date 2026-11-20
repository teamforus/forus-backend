<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Products;

use App\Http\Requests\BaseFormRequest;
use App\Models\Product;
use Illuminate\Validation\Rule;

class IndexProductsRequest extends BaseFormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'to' => 'nullable|date:Y-m-d',
            'from' => 'nullable|date:Y-m-d',
            'fund_id' => 'nullable|integer',
            'unlimited_stock' => 'sometimes|boolean',
            'expired' => 'sometimes|boolean',
            'sold_out' => 'sometimes|boolean',
            'price_types' => 'sometimes|array',
            'price_types.*' => ['required', 'string', Rule::in(Product::PRICE_TYPES)],
            'updated_to' => 'nullable|date:Y-m-d',
            'updated_from' => 'nullable|date:Y-m-d',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'has_reservations' => 'nullable|boolean',
            'state' => 'nullable|in:approved,pending',
            ...$this->sortableResourceRules(100, [
                'name', 'last_monitored_change_at',
            ]),
        ];
    }
}
