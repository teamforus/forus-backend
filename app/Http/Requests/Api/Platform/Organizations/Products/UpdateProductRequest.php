<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use App\Models\Product;
use App\Rules\MediaUidRule;

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
        $price_type = $this->input('price_type');
        $minAmount = $product->countReserved() + $product->countSold();

        return array_merge([
            'name'                  => 'required|between:2,200',
            'description'           => 'required|between:5,2500',
            'alternative_text'      => 'nullable|between:2,500',
            'price'                 => 'required_if:price_type,regular|numeric|min:.2',
            'price_type'            => 'required|in:' . implode(',', Product::PRICE_TYPES),

            'price_discount' => match ($price_type) {
                'discount_fixed' => 'required|numeric|min:.1',
                'discount_percentage' => 'required|numeric|between:.1,100',
                default => [],
            },

            'total_amount' => [
                $product->unlimited_stock ? null : 'required',
                'numeric',
                $product->unlimited_stock ? null : 'min:' . $minAmount,
            ],

            'expire_at' => 'nullable|date_format:Y-m-d|after:today',
            'product_category_id' => 'required|exists:product_categories,id',
            'media_uid' => ['nullable', new MediaUidRule('product_photo')],
        ], ...$this->reservationRules());
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'price_discount.required_if' => 'Het kortingsveld is verplicht.',
            'expire_at.after' => trans('validation.after', [
                'date' => trans('validation.attributes.today')
            ]),
        ];
    }
}
    