<?php

namespace App\Http\Requests\Api\Platform\Vouchers\PhysicalCardRequests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Voucher;
use App\Models\VoucherToken;
use Illuminate\Validation\Rule;

/**
 * @property ?Voucher $voucher
 * @property ?Voucher $voucher_number_or_address
 * @property ?VoucherToken $voucher_token_address
 */
class StorePhysicalCardRequestRequest extends BaseFormRequest
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
        $voucher = $this->voucher ?: $this->voucher_number_or_address ?: $this->voucher_token_address?->voucher;

        return [
            'address' => 'required|street_name',
            'house' => 'required|house_number',
            'house_addition' => 'nullable|house_addition',
            'postcode' => 'required|postcode',
            'city' => 'required|city_name',
            'fund_physical_card_type_id' => [
                'required',
                Rule::exists('fund_physical_card_types', 'id')->where('fund_id', $voucher->fund_id),
            ],
        ];
    }
}
