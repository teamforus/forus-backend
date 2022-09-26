<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Rules\Base\IbanRule;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

/**
 * @property Organization $organization
 */
class StoreTransactionRequest extends BaseFormRequest
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
        $voucher = $this->has('voucher_id') ? Voucher::find($this->input('voucher_id')) : null;

        return array_merge([
            'voucher_id' => [
                'required',
                Rule::in($this->voucherIds()),
            ],
            'organization_id' => [
                'required_if:target,' . VoucherTransaction::TARGET_PROVIDER,
                Rule::in($this->fundProviderIds($voucher)),
            ],
            'note' => 'nullable|string|max:255',
            'amount' => $this->amountRule($voucher),
        ], $this->targetRules($voucher));
    }

    /**
     * @param Voucher|null $voucher
     * @return array
     */
    protected function targetRules(?Voucher $voucher): array
    {
        $fundConfig = $voucher?->fund?->fund_config;
        $allowTopUps = (bool) $fundConfig?->allow_voucher_top_ups;
        $allowDirectDirectPayments = (bool) $fundConfig?->allow_direct_payments;

        $targets = [
            $allowDirectDirectPayments ? VoucherTransaction::TARGET_IBAN : null,
            $allowTopUps ? VoucherTransaction::TARGET_TOP_UP : null,
            VoucherTransaction::TARGET_PROVIDER
        ];

        return [
            'target' => [
                'required', Rule::in(array_filter($targets)),
            ],
            'target_iban' => [
                'required_if:target,' . VoucherTransaction::TARGET_IBAN, new IbanRule(),
            ],
            'target_name' => [
                'required_if:target,' . VoucherTransaction::TARGET_IBAN, 'string', 'min:3', 'max:200',
            ],
        ];
    }

    /**
     * @param Voucher|null $voucher
     * @return string
     */
    protected function amountRule(?Voucher $voucher): string
    {
        $max = match($this->input('target')) {
            VoucherTransaction::TARGET_IBAN,
            VoucherTransaction::TARGET_PROVIDER => $voucher->amount_available,
            VoucherTransaction::TARGET_TOP_UP => min([
                $voucher->fund->fund_config->limit_voucher_top_up_amount,
                $voucher->fund->fund_config->limit_voucher_total_amount - $voucher->amount_total,
            ]),
            default => 0,
        };

        return 'required|numeric|min:.02|max:' . currency_format($max);
    }

    /**
     * @return array
     */
    protected function voucherIds(): array
    {
        $builder = VoucherQuery::whereNotExpiredAndActive(Voucher::query());

        $builder = $builder->whereHas('fund', function(Builder $builder) {
            $builder->where('funds.type', Fund::TYPE_BUDGET);

            FundQuery::whereIsInternalConfiguredAndActive($builder->where([
                'organization_id' => $this->organization->id,
            ]))->select('funds.id');
        });

        return $builder->whereNull('product_id')->pluck('vouchers.id')->toArray();
    }

    /**
     * @param Voucher|null $voucher
     * @return array
     */
    protected function fundProviderIds(?Voucher $voucher): array
    {
        return $voucher ? FundProviderQuery::whereApprovedForFundsFilter(
            FundProvider::query(),
            $voucher->fund_id,
            'budget'
        )->whereHas('fund', function(Builder $builder) {
            $builder->where('type', Fund::TYPE_BUDGET);
        })->pluck('fund_providers.organization_id')->toArray() : [];
    }
}
