<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use App\Http\Requests\BaseFormRequest;
use App\Models\Product;
use App\Rules\MediaUidRule;

/**
 * Class StoreProductRequest
 * @package App\Http\Requests\Api\Platform\Organizations\Products
 */
class StoreProductRequest extends BaseFormRequest
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
        $unlimited_stock = $this->input('unlimited_stock', false);
        $price_type = $this->input('price_type');

        return [
            'name'                  => 'required|between:2,200',
            'description'           => 'required|between:5,2500',
            'price'                 => 'required_if:price_type,regular|numeric|min:.2',
            'media_uid'             => ['nullable', 'string', new MediaUidRule('product_photo')],
            'price_type'            => 'required|in:' . join(',', Product::PRICE_TYPES),
            'price_discount'        => [
                'discount_fixed'        => 'required_if:price_type,discount_fixed|min:.1',
                'discount_percentage'   => 'required_if:price_type,discount_percentage|between:.1,100',
            ][$price_type] ?? [],
            'unlimited_stock'       => 'boolean',
            'total_amount'          => [
                $unlimited_stock ? null : 'required',
                'numeric',
                'min:1'
            ],
            'expire_at'             => 'nullable|date_format:Y-m-d|after:today',
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

    public function attributes(): array
    {
        return [
            'price_type.free' => 'gratis',
            'price_type.discount_fixed' => 'korting',
            'price_type.discount_percentage' => 'korting',
        ];
    }
}
