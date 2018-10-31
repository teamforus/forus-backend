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
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $price = $this->get('price', 0);

        return [
            'name'                  => 'required|between:2,200',
            'description'           => 'required|between:5,1000',
            'price'                 => 'required|numeric|min:.01',
            'old_price'             => 'nullable|numeric|min:' . $price,
            'total_amount'          => 'required|numeric|min:1',
            'expire_at'             => 'required|date|after:today',
            'product_category_id'   => 'required|exists:product_categories,id',
        ];
    }
}
