<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Models\Fund;
use App\Rules\VouchersUploadArrayRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVoucherRequest extends FormRequest
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
        $fund = Fund::find($this->input('fund_id'));
        $endDate = $fund ? $fund->end_date->format('Y-m-d') : 'today';
        $max_allowed = config('forus.funds.max_sponsor_voucher_amount');
        $max = min($fund ? $fund->budget_left : $max_allowed, $max_allowed);

        $precision = $fund->currency == $fund::CURRENCY_ETHER ? 5 : 2;
        $min = $precision == 5 ? '.001' : '.1';

        $max = number_format($max, $precision,'.', '');

        logger()->debug(json_encode([$fund->budget_left, $max]));

        return [
            'fund_id'           => 'required|exists:funds,id',
            'note'              => 'nullable|string|max:280',
            'amount'            => 'required_without:vouchers|numeric|between:' . join(',', [$min, $max]),
            'expires_at'        => 'nullable|date_format:Y-m-d|after:' . $endDate,
            'email'             => 'nullable|email',
            'vouchers'          => [
                'required_without:amount',
                new VouchersUploadArrayRule($fund)
            ],
            'vouchers.*'            => 'required|array',
            'vouchers.*.amount'     => 'required|numeric|between:' . join(',', [$min, $max]),
            'vouchers.*.expires_at' => 'nullable|date_format:Y-m-d|after:' . $endDate,
            'vouchers.*.note'       => 'nullable|string|max:280',
            'vouchers.*.email'      => 'nullable|email',
        ];
    }
}
