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

        $targets = ($voucher && $voucher->fund->fund_config->limit_voucher_top_up_amount)
            ? VoucherTransaction::TARGETS
            : [VoucherTransaction::TARGET_IDENTITY, VoucherTransaction::TARGET_PROVIDER];

        return [
            'voucher_id' => [
                'required',
                Rule::in($this->voucherIds()),
            ],
            'target' => [
                'required',
                Rule::in($targets),
            ],
            'target_iban' => [
                'required_if:target,' . VoucherTransaction::TARGET_IDENTITY,
                new IbanRule(),
            ],
            'target_name' => [
                'required_if:target,' . VoucherTransaction::TARGET_IDENTITY,
                'string',
                'min:3',
                'max:200',
            ],
            'provider_id' => [
                'required_if:target,' . VoucherTransaction::TARGET_PROVIDER,
                Rule::in($this->fundProviderIds($voucher)),
            ],
            'note' => 'nullable|string|max:255',
            'amount' => $this->amountRule($voucher),
        ];
    }

    /**
     * @param Voucher|null $voucher
     * @return string
     */
    protected function amountRule(?Voucher $voucher): string
    {
        $max = 0;
        $target = $this->input('target');

        if ($voucher) {
            $max = $target === VoucherTransaction::TARGET_SELF
                ? ($voucher->fund->fund_config->limit_voucher_top_up_amount ?? 0)
                : $voucher->amount_available;
        }

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
