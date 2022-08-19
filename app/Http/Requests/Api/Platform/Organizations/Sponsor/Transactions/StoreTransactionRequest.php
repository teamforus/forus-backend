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
        return [
            'voucher_id' => [
                'required',
                Rule::in($this->voucherIds()),
            ],
            'target' => [
                'required',
                Rule::in(VoucherTransaction::TARGETS),
            ],
            'target_iban' => [
                'required_if:target,' . VoucherTransaction::TARGET_IDENTITY,
                new IbanRule(),
            ],
            'provider_id' => [
                'required_if:target,' . VoucherTransaction::TARGET_PROVIDER,
                Rule::in($this->fundProviderIds()),
            ],
            'note' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:.02|max:' . currency_format($this->maxAmount()),
        ];
    }

    /**
     * @return float
     */
    protected function maxAmount(): float
    {
        $voucher = $this->has('voucher_id') ? Voucher::find($this->input('voucher_id')) : null;

        if ($voucher) {
            return $voucher->amount_available;
        }

        return 0;
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
     * @return array
     */
    protected function fundProviderIds(): array
    {
        $voucher = $this->has('voucher_id') ? Voucher::find($this->input('voucher_id')) : null;

        return $voucher ? FundProviderQuery::whereApprovedForFundsFilter(
            FundProvider::query(),
            $voucher->fund_id,
            'budget'
        )->whereHas('fund', function(Builder $builder) {
            $builder->where('type', Fund::TYPE_BUDGET);
        })->pluck('fund_providers.organization_id')->toArray() : [];
    }
}
