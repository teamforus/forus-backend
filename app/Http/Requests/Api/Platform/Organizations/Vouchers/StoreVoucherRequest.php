<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Models\Fund;
use App\Models\VoucherRelation;
use App\Rules\BsnRule;
use App\Rules\ProductIdInStockRule;
use App\Scopes\Builders\FundQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class StoreVoucherRequest extends BaseStoreVouchersRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $fund = $this->getFund();
        $bsn_enabled = $this->organization->bsn_enabled;

        return [
            'bsn' => $this->bsnRule($bsn_enabled),
            'note' => 'nullable|string|max:280',
            'email' => [
                'nullable',
                'required_if:assign_by_type,email',
                ...$this->emailRules(),
            ],
            'amount' => $this->amountRule($fund),
            'records' => $this->recordsRule(),
            'fund_id' => $this->fundIdRule(),
            'activate' => 'boolean',
            'expire_at' => $this->expireAtRule($fund),
            'client_uid' => 'nullable|string|max:20',
            'product_id' => $this->productIdRule($fund),
            'assign_by_type' => 'required|in:' . $this->availableAssignTypes($bsn_enabled),
            'activation_code' => 'boolean',
            'limit_multiplier' => 'nullable|numeric|min:1|max:1000',
            'report_type' => ['nullable', Rule::in(VoucherRelation::REPORT_TYPES)],
            'notify_provider' => 'nullable|boolean',
        ];
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'assign_by_type' => 'methode',
        ];
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        return [
            'amount.between' => 'Er staat niet genoeg budget op het fonds. ' .
                'Het maximale tegoed van een voucher is €:max. ' .
                'Vul het fonds aan om deze voucher aan te maken.',
        ];
    }

    /**
     * @param bool $bsn_enabled
     * @return string
     */
    protected function availableAssignTypes(bool $bsn_enabled): string
    {
        return implode(',', array_filter([
            'email', 'activation_code', $bsn_enabled ? 'bsn' : null,
        ]));
    }

    /**
     * @return array
     */
    private function fundIdRule(): array
    {
        $fundIds = $this->organization->funds()->where(function (Builder $builder) {
            FundQuery::whereIsInternal($builder);
            FundQuery::whereIsConfiguredByForus($builder);
        })->pluck('id')->toArray();

        return [
            'required',
            Rule::exists('funds', 'id')->whereIn('id', $fundIds),
        ];
    }

    /**
     * @param Fund $fund
     * @return string|string[]
     */
    private function amountRule(Fund $fund): array|string
    {
        return $fund->isTypeBudget() ? [
            'nullable',
            'required_without:product_id',
            'numeric',
            'between:.1,' . currency_format($fund->getMaxAmountPerVoucher()),
        ] : 'nullable';
    }

    /**
     * @param Fund $fund
     * @return string[]
     */
    private function productIdRule(Fund $fund): array
    {
        $rule = $fund->isTypeBudget() ? [
            'nullable', 'required_without:amount',
        ] : [
            'nullable',
        ];

        return array_merge($rule, [
            'exists:products,id',
            new ProductIdInStockRule($fund),
        ]);
    }

    /**
     * @param Fund $fund
     * @return string[]
     */
    private function expireAtRule(Fund $fund): array
    {
        return [
            'nullable',
            'date_format:Y-m-d',
            'after:' . $fund->start_date->format('Y-m-d'),
            'before_or_equal:' . $fund->end_date->format('Y-m-d'),
        ];
    }

    /**
     * @param bool $bsn_enabled
     * @return string[]
     */
    private function bsnRule(bool $bsn_enabled): array
    {
        return $bsn_enabled ? [
            'nullable', 'required_if:assign_by_type,bsn', new BsnRule(),
        ] : [
            'nullable', 'in:',
        ];
    }

    /**
     * @return Fund
     */
    private function getFund(): Fund
    {
        /** @var Fund $fund */
        $fund = $this->organization->funds()->findOrFail($this->input('fund_id'));

        return $fund;
    }
}
