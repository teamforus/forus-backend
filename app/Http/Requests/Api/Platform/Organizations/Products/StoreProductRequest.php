<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use App\Models\Product;

class StoreProductRequest extends BaseProductRequest
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
        $unlimited_stock = $this->post('unlimited_stock', false);
        $price_type = $this->post('price_type');

        return [
            ...$this->baseProductRules((string) $price_type, null),
            ...$this->reservationRules(),
            'unlimited_stock' => 'boolean',
            'total_amount' => [
                $unlimited_stock || ($price_type === Product::PRICE_TYPE_INFORMATIONAL) ? 'nullable' : 'required',
                'numeric',
                'min:1',
            ],
        ];
    }
}
