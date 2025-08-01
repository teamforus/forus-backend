<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use App\Models\Product;

/**
 * @property Product $product
 */
class UpdateProductRequest extends BaseProductRequest
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
        $product = $this->product;
        $minAmount = $product->countReserved() + $product->countSold();

        return [
            ...$this->baseProductRules((string) $this->input('price_type'), $product),
            ...$this->reservationRules(),

            'total_amount' => [
                $product->unlimited_stock ? null : 'required',
                'numeric',
                $product->unlimited_stock ? null : 'min:' . $minAmount,
            ],
        ];
    }
}
