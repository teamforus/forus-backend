<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use App\Http\Requests\BaseFormRequest;

class IndexProductRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return (mixed|string)[]
     *
     * @psalm-return array{source: 'nullable|in:sponsor,provider,archive'|mixed, unlimited_stock: 'nullable|boolean'|mixed,...}
     */
    public function rules(): array
    {
        return [
            'source' => 'nullable|in:sponsor,provider,archive',
            'unlimited_stock' => 'nullable|boolean',
            ...$this->sortableResourceRules(100, [
                'id', 'name', 'stock_amount', 'price', 'expire_at',
            ]),
        ];
    }
}
