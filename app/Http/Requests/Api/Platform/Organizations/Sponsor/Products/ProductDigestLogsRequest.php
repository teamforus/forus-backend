<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Products;

use App\Models\Organization;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
class ProductDigestLogsRequest extends IndexProductsRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'fund_id' => [
                'nullable',
                Rule::in($this->organization->funds()->pluck('id')->toArray()),
            ],
            'product_id' => 'nullable|integer',
            'group_by'   => 'nullable|in:per_product,null',
        ]);
    }
}