<?php

namespace App\Http\Requests\Api\Platform\Vouchers;

use App\Models\Fund;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Rules\ProductIdToVoucherRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVoucherRequest extends FormRequest
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
        $identity_address = auth_address();

        return [
            'voucher_address' => [
                'required',
                'exists:voucher_tokens,address',
                Rule::in($identity_address ? VoucherToken::whereHas('voucher', static function(
                    Builder $query
                ) use ($identity_address) {
                    $query->where([
                        'identity_address' => $identity_address,
                    ])->whereHas('fund', static function(Builder $builder) {
                        $builder->where('type', '=', Fund::TYPE_BUDGET);
                    })->whereNull('product_id');
                })->pluck('address')->toArray() : [])
            ],
            'product_id' => [
                'required',
                'exists:products,id',
                new ProductIdToVoucherRule($this->input('voucher_address'))
            ],
        ];
    }
}
