<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Products;

use App\Http\Requests\BaseFormRequest;

class ProductDigestLogsRequest extends BaseFormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'product_id' => 'nullable|integer',
            'group_by'   => 'nullable|in:per_product,null',
        ];
    }
}