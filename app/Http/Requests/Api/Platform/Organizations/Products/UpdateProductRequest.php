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
        $currentExpire = $product->expire_at->format('Y-m-d');
        $minAmount = $product->countReserved() + $product->countSold();

        $price = rule_number_format($this->get('price', 0));

        return [
            'name'                  => 'required|between:2,200',
            'description'           => 'required|between:5,2500',
            'no_price'              => 'boolean',
            'price'                 => $product->no_price ? [] : 'required_without:no_price|numeric|min:.2',
            'old_price'             => $product->no_price ? [] : [
                'nullable',
                'numeric',
                'min:' . $price
            ],
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
            'expire_at.after' => trans('validation.after', [
                'date' => trans('validation.attributes.today')
            ])
        ];
    }
}
    