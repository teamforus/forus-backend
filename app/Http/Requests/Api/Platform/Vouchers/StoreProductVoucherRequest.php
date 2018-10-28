<?php

namespace App\Http\Requests\Api\Platform\Vouchers;

use App\Rules\ProductIdToVoucherRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVoucherRequest extends FormRequest
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
        return [
            'voucher_address'    => [
                'required',
                'exists:voucher_tokens,address'
            ],
            'product_id'    => [
                'required',
                'exists:products,id',
                new ProductIdToVoucherRule(request()->input('voucher_address'))
            ],
        ];
    }
}
