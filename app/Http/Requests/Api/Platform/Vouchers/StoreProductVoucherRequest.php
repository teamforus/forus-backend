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
        $identityAddress = auth()->user()->getAuthIdentifier();

        return [
            'voucher_address'    => [
                'required',
                Rule::exists('vouchers', 'address')->where(function(
                    Builder $query
                ) use ($identityAddress) {
                    $query->where([
                        'identity_address'  => $identityAddress,
                        'parent_id'         => null
                    ]);
                })
            ],
            'product_id'    => [
                'required',
                'exists:products,id',
                new ProductIdToVoucherRule(request()->input('voucher_address'))
            ],
        ];
    }
}
