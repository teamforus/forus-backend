<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Rules\ProductIdInStockRule;
use App\Rules\VouchersUploadArrayRule;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Validation\Rule;

class StoreBatchVoucherRequest extends BaseFormRequest
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
        $validFunds = $this->validFundIds($this->auth_address());
        $fund = Fund::query()->whereIn('id', $validFunds)->findOrFail($this->input('fund_id'));

        $max_allowed = config('forus.funds.max_sponsor_voucher_amount');
        $max = min($fund->budget_left ?? $max_allowed, $max_allowed);

        return [
            'fund_id'   => [
                'required',
                Rule::exists('funds', 'id')->whereIn('id', $validFunds)
            ],
            'vouchers' => [
                'required',
                new VouchersUploadArrayRule($fund),
            ],
            'vouchers.*' => 'required|array',
            'vouchers.*.amount' => !$fund || $fund->isTypeBudget() ? [
                'required_without:vouchers.*.product_id',
                'numeric',
                'between:.1,' . currency_format($max),
            ] : 'nullable',
            'vouchers.*.product_id' => !$fund || $fund->isTypeBudget() ? [
                'required_without:vouchers.*.amount',
                'exists:products,id',
                new ProductIdInStockRule($fund, collect(
                    $this->input('vouchers')
                )->countBy('product_id')->toArray())
            ] : [
                'nullable',
                Rule::in([])
            ],
            'vouchers.*.expire_at' => [
                'nullable',
                'date_format:Y-m-d',
                'after:' . $fund->start_date->format('Y-m-d'),
                'before_or_equal:' . $fund->end_date->format('Y-m-d'),
            ],
            'vouchers.*.note'       => 'nullable|string|max:280',
            'vouchers.*.email'      => 'nullable|email:strict,dns',
            'vouchers.*.bsn'        => 'nullable|string|between:8,9',
        ];
    }

    /**
     * @param $identity_address
     * @return array
     */
    private function validFundIds($identity_address): array {
        return Fund::whereHas('organization', static function($builder) use ($identity_address) {
            OrganizationQuery::whereHasPermissions($builder, $identity_address, 'manage_vouchers');
        })->pluck('funds.id')->toArray();
    }
}
