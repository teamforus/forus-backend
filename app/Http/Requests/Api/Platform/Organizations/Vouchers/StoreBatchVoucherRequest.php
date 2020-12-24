<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Rules\ProductIdInStockRule;
use App\Rules\VouchersUploadArrayRule;
use Illuminate\Validation\Rule;

/**
 * Class StoreBatchVoucherRequest
 * @property-read Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Vouchers
 */
class StoreBatchVoucherRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->organization->identityCan($this->auth_address(), [
            'manage_vouchers'
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        /** @var Fund $fund */
        $funds = $this->organization->funds();
        $fund = $funds->findOrFail($this->input('fund_id'));

        $max_allowed = config('forus.funds.max_sponsor_voucher_amount');
        $max = min($fund->budget_left ?? $max_allowed, $max_allowed);

        return [
            'fund_id'   => [
                'required',
                Rule::exists('funds', 'id')->whereIn('id', $funds->pluck('id')->toArray())
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
            'vouchers.*.product_id' => [
                'required_without:vouchers.*.amount',
                'exists:products,id',
                new ProductIdInStockRule($fund, collect(
                    $this->input('vouchers')
                )->countBy('product_id')->toArray())
            ],
            'vouchers.*.expire_at' => [
                'nullable',
                'date_format:Y-m-d',
                'after:' . $fund->start_date->format('Y-m-d'),
                'before_or_equal:' . $fund->end_date->format('Y-m-d'),
            ],
            'vouchers.*.note'                   => 'nullable|string|max:280',
            'vouchers.*.email'                  => 'nullable|string|email:strict,dns',
            'vouchers.*.bsn'                    => 'nullable|string|digits:9',
            'vouchers.*.activate'               => 'boolean',
            'vouchers.*.activation_code'        => 'boolean',
            'vouchers.*.activation_code_uid'    => 'nullable|string|max:20',
        ];
    }
}
