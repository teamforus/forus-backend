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
            'q' => 'nullable|string',
            'source' => 'nullable|in:sponsor,provider,archive',
            'unlimited_stock' => 'nullable|boolean',
            'per_page' => $this->perPageRule(),
            'order_by'  => 'nullable|in:id,name,stock_amount,price,expired,expire_at',
            'order_dir' => 'nullable|in:asc,desc',
        ];
    }
}
