<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateProductRequest
 * @property Product $product
 * @package App\Http\Requests\Api\Platform\Organizations\Products
 */
class UpdateProductRequest extends FormRequest
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
        $currentExpire = $product->expire_at->format('Y-m-d');
        $minAmount = $product->countReserved() + $product->countSold();

        return [
            'name'                  => 'required|between:2,200',
            'description'           => 'required|between:5,2500',
            'price'                 => 'required_if:price_type,regular|numeric|min:.2',
            'price_type'            => 'required|in:' . join(',', Product::PRICE_TYPES),
            'price_discount'        => [
                'discount_fixed'        => 'required_if:price_type,discount_fixed|numeric|min:.1',
                'discount_percentage'   => 'required_if:price_type,discount_percentage|numeric|between:.1,100',
            ][$price_type] ?? [],
            'total_amount'          => [
                $product->unlimited_stock ? null : 'required',
                'numeric',
                $product->unlimited_stock ? null : 'min:' . $minAmount,
            ],
            'expire_at'             => 'required|date|after:today|after_or_equal:' . $currentExpire,
            'product_category_id'   => 'required|exists:product_categories,id',
        ];
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
            ])
        ];
    }
}
    