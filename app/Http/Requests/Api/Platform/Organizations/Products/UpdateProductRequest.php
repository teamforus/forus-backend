<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use App\Http\Requests\BaseFormRequest;
use App\Models\Product;
use App\Rules\MediaUidRule;

/**
 * Class UpdateProductRequest
 * @property Product $product
 * @package App\Http\Requests\Api\Platform\Organizations\Products
 */
class UpdateProductRequest extends BaseFormRequest
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

        return [
            'name'                  => 'required|between:2,200',
            'description'           => 'required|between:5,2500',
            'price'                 => 'required_if:price_type,regular|numeric|min:.2',
            'price_type'            => 'required|in:' . join(',', Product::PRICE_TYPES),
            'price_discount'        => [
                'discount_fixed'        => 'required|numeric|min:.1',
                'discount_percentage'   => 'required|numeric|between:.1,100',
            ][$price_type] ?? [],
            'total_amount'          => [
                $product->unlimited_stock ? null : 'required',
                'numeric',
                $product->unlimited_stock ? null : 'min:' . $minAmount,
            ],

            'expire_at'             => 'nullable|date_format:Y-m-d|after:today',
            'product_category_id'   => 'required|exists:product_categories,id',
            'media_uid'             => ['nullable', new MediaUidRule('product_photo')],
            'reservation_enabled'   => 'nullable|boolean',
            'reservation_policy'    => 'nullable|in:' . join(',', Product::RESERVATION_POLICIES),
            'reservation_phone'     => 'required|in:no,global,optional,required',
            'reservation_address'   => 'required|in:no,global,optional,required',
            'reservation_requester_birth_date' => 'required|in:no,global,optional,required',
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
            ]),
        ];
    }
}
    