<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Models\Fund;
use App\Rules\VouchersUploadArrayRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Database\Query\Builder;

class StoreBatchVoucherRequest extends FormRequest
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
        $providers = $fund ?
            $fund->provider_organizations_approved->pluck('id') : collect();

        $productIdRule = Rule::exists('products', 'id')->where(function (
            Builder $query
        ) use ($providers) {
            $query->whereIn('organization_id', $providers->toArray());
        });

        return [
            'fund_id'   => 'required|exists:funds,id',
            'vouchers' => [
                'required_without_all:amount,product_id',
                new VouchersUploadArrayRule($fund),
            ],
            'vouchers.*' => 'required|array',
            'vouchers.*.amount' => [
                'required_without:vouchers.*.product_id', 'numeric',
                'between:.1,' . $max,
            ],
            'vouchers.*.product_id' => [
                'required_without:vouchers.*.amount', $productIdRule,
            ],
            'vouchers.*.expires_at' => [
                'nullable', 'date_format:Y-m-d', 'after:' . $endDate,
            ],
            'vouchers.*.note'       => 'nullable|string|max:280',
            'vouchers.*.email'      => 'nullable|email',
        ];
    }
}
