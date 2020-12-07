<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
        $price = rule_number_format($this->input('price', 0));
        $unlimited_stock = $this->input('unlimited_stock', false);

        return [
            'name'                  => 'required|between:2,200',
            'description'           => 'required|between:5,1000',
            'price'                 => 'required_without:no_price|numeric|min:.2|max:10000',
            'no_price'              => 'boolean',
            'no_price_type'         => 'required_with:no_price|in:free,discount',
            'no_price_discount'     => 'nullable|required_if:no_price_type,discount|numeric|min:0|max:100',
            'unlimited_stock'       => 'boolean',
            'old_price'             => 'nullable|numeric|min:' . $price,
            'total_amount'          => [
                $unlimited_stock ? null : 'required',
                'numeric',
                'min:1'
            ],
            'expire_at'             => 'required|date_format:Y-m-d|after:today',
            'product_category_id'   => 'required|exists:product_categories,id',
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'no_price_discount.required_if' => 'Het kortingsveld is verplicht.',
            'expire_at.after' => trans('validation.after', [
                'date' => trans('validation.attributes.today')
            ])
        ];
    }

    public function attributes(): array
    {
        return [
            'no_price_type.free' => 'gratis',
            'no_price_type.discount' => 'korting',
        ];
    }
}
