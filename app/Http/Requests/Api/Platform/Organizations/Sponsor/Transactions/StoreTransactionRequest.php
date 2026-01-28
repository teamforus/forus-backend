<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\VoucherQuery;
use App\Traits\ResolvesPayoutBankAccountPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * @property Organization $organization
 */
class StoreTransactionRequest extends BaseFormRequest
{
    use ResolvesPayoutBankAccountPayload;

    protected ?array $voucherIds = null;
    protected ?Voucher $voucher = null;

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
        $voucher = $this->getVoucher();

        return [
            'voucher_id' => [
                'required',
                Rule::in($this->voucherIdList()),
            ],
            'organization_id' => [
                'required_if:target,' . VoucherTransaction::TARGET_PROVIDER,
                Rule::in($this->fundProviderIds($voucher)),
            ],
            'note' => 'nullable|string|max:255',
            'note_shared' => 'nullable|boolean',
            'amount' => $this->amountRule($voucher),
            ...$this->targetRules($voucher),
        ];
    }

    /**
     * @param Validator $validator
     * @return void
     * @noinspection PhpUnused
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('target') === VoucherTransaction::TARGET_IBAN) {
                $this->validateSingleBankAccountSource($validator);
            }
        });
    }

    /**
     * @param Voucher|null $voucher
     * @return array
     */
    protected function targetRules(?Voucher $voucher): array
    {
        $fundConfig = $voucher?->fund?->fund_config;
        $allowTopUps = (bool) $fundConfig?->allow_voucher_top_ups;
        $allowDirectPayments = (bool) $fundConfig?->allow_direct_payments;

        $targets = [
            $allowDirectPayments ? VoucherTransaction::TARGET_IBAN : null,
            $allowTopUps ? VoucherTransaction::TARGET_TOP_UP : null,
            VoucherTransaction::TARGET_PROVIDER,
        ];

        return array_merge([
            'target' => ['required', Rule::in(array_filter($targets))],
        ], $this->input('target') == VoucherTransaction::TARGET_IBAN ? [
            'fund_request_id' => $this->fundRequestIdRules(),
            'profile_bank_account_id' => $this->profileBankAccountIdRules(),
            'reimbursement_id' => $this->reimbursementIdRules(),
            'payout_transaction_id' => $this->payoutTransactionIdRules(),
            'target_iban' => $this->targetIbanRules(),
            'target_name' => $this->targetNameRules(),
        ] : []);
    }

    /**
     * @param Voucher|null $voucher
     * @return string
     */
    protected function amountRule(?Voucher $voucher): string
    {
        if (!$voucher) {
            return 'required|numeric|min:.02|max:0';
        }

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
     * @return Builder|Voucher
     */
    protected function vouchersQuery(): Builder|Voucher
    {
        $builder = VoucherQuery::whereNotExpiredAndActive(Voucher::query());

        $builder = $builder->whereHas('fund', function (Builder $builder) {
            FundQuery::whereIsInternalConfiguredAndActive($builder->where([
                'organization_id' => $this->organization->id,
            ]))->select('funds.id');
        });

        return $builder->whereNull('product_id');
    }

    /**
     * @param Voucher|null $voucher
     * @return array
     */
    protected function fundProviderIds(?Voucher $voucher): array
    {
        if (!$voucher) {
            return [];
        }

        return FundProviderQuery::whereApprovedForFundsFilter(FundProvider::query(), $voucher->fund_id, 'allow_budget')
            ->whereHas('fund', fn (Builder $builder) => FundQuery::whereIsInternal($builder))
            ->pluck('fund_providers.organization_id')->toArray();
    }

    /**
     * @return Voucher|null
     */
    protected function getVoucher(): ?Voucher
    {
        if ($this->voucher !== null) {
            return $this->voucher;
        }

        return $this->voucher = $this->has('voucher_id')
            ? $this->vouchersQuery()->find($this->input('voucher_id'))
            : null;
    }

    /**
     * @return array
     */
    protected function voucherIdList(): array
    {
        if ($this->voucherIds !== null) {
            return $this->voucherIds;
        }

        return $this->voucherIds = $this->vouchersQuery()->pluck('vouchers.id')->toArray();
    }

    /**
     * @return array
     */
    protected function bankAccountSearchFilters(): array
    {
        return [
            'identity_id' => $this->getVoucher()?->identity_id ?? -1,
        ];
    }
}
