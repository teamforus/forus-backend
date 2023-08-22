<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use App\Http\Requests\BaseFormRequest;

class IndexProductRequest extends BaseFormRequest
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
            'source' => 'nullable|in:sponsor,provider,archive',
            'unlimited_stock' => 'nullable|boolean',
            ...$this->sortableResourceRules(100, [
                'id', 'name', 'stock_amount', 'price', 'expire_at',
            ]),
        ];
    }
}
