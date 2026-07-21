<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts;

use App\Http\Requests\BaseFormRequest;
use App\Models\Data\PayoutAmount;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\VoucherQuery;
use App\Traits\ResolvesPayoutBankAccountPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * @property Organization $organization
 */
class StorePayoutTransactionRequest extends BaseFormRequest
{
    use ResolvesPayoutBankAccountPayload;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        if (!Gate::allows('storePayoutsSponsor', [VoucherTransaction::class, $this->organization])) {
            return false;
        }

        return !$this->isVoucherBackedPayout() || $this->organization->identityCan($this->identity(), [
            Permission::MANAGE_VOUCHERS,
            Permission::VIEW_VOUCHERS,
        ], false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $fund = $this->getFundsQuery()->find($this->input('fund_id'));

        return [
            'funding_type' => ['sometimes', 'string', Rule::in(VoucherTransaction::PAYOUT_FUNDING_TYPES)],
            'fund_id' => $this->fundIdsRules(),
            'voucher_id' => $this->voucherIdRules(),
            'fund_request_id' => $this->fundRequestIdRules(),
            'profile_bank_account_id' => $this->profileBankAccountIdRules(),
            'reimbursement_id' => $this->reimbursementIdRules(),
            'payout_transaction_id' => $this->payoutTransactionIdRules(),
            'amount' => [
                'required_without:amount_preset_id',
                ...$this->amountRules($fund),
            ],
            'amount_preset_id' => [
                'required_without:amount',
                ...$this->amountOptionIdRules($fund, 'id'),
            ],
            'target_iban' => $this->isVoucherBackedPayout() ? ['prohibited'] : $this->targetIbanRules(),
            'target_name' => $this->isVoucherBackedPayout() ? ['prohibited'] : $this->targetNameRules(),
            'bsn' => ['nullable', ...$this->bsnRules()],
            'email' => ['nullable', ...$this->emailRules()],
            'description' => $this->descriptionRules(),
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
            if ($this->isVoucherBackedPayout()) {
                $this->validateVoucherBackedPayout($validator);
            } else {
                $this->validateSingleBankAccountSource($validator);
            }
        });
    }

    /**
     * @return string
     */
    public function fundingType(): string
    {
        $fundingType = $this->input('funding_type');

        return is_string($fundingType) ? $fundingType : VoucherTransaction::PAYOUT_FUNDING_TYPE_STANDALONE;
    }

    /**
     * @return bool
     */
    public function isVoucherBackedPayout(): bool
    {
        return $this->fundingType() === VoucherTransaction::PAYOUT_FUNDING_TYPE_VOUCHER;
    }

    /**
     * @param Fund $fund
     * @return PayoutAmount
     */
    public function resolvePayoutAmount(Fund $fund): PayoutAmount
    {
        $field = $this->filled('amount_preset_id') ? 'amount_preset_id' : 'amount';
        $preset = $field === 'amount_preset_id' ? $fund->amount_presets()->find($this->input('amount_preset_id')) : null;

        if ($field === 'amount_preset_id' && !$preset) {
            throw ValidationException::withMessages([
                'amount_preset_id' => [trans('validation.in', ['attribute' => 'amount_preset_id'])],
            ]);
        }

        return new PayoutAmount(
            field: $field,
            amount: currency_format($preset?->amount ?? $this->input('amount')),
            preset: $preset,
        );
    }

    /**
     * @return Fund|Builder|Relation
     */
    protected function getFundsQuery(): Fund|Builder|Relation
    {
        return FundQuery::whereIsInternalConfiguredAndActive($this->organization->funds());
    }

    /**
     * @return string[]
     */
    protected function fundIdsRules(): array
    {
        return [
            'required',
            Rule::in($this->getFundsQuery()->pluck('id')->toArray()),
        ];
    }

    /**
     * @return array
     */
    protected function voucherIdRules(): array
    {
        return $this->isVoucherBackedPayout() ? ['required', 'integer'] : ['prohibited'];
    }

    /**
     * @return string[]
     */
    protected function uploadBatchId(): array
    {
        return [
            'nullable',
            Rule::exists('voucher_transactions', 'upload_batch_id')
                ->whereNotNull('employee_id')
                ->where('employee_id', $this->employee($this->organization)?->id),
        ];
    }

    /**
     * @return array
     */
    protected function descriptionRules(): array
    {
        return ['nullable', 'string', 'max:500'];
    }

    /**
     * @param Fund|null $fund
     * @return array|string[]
     */
    protected function amountRules(?Fund $fund): array
    {
        if (!$fund?->fund_config?->allow_custom_amounts) {
            return [Rule::in([])];
        }

        return [
            'numeric',
            'min:' . currency_format($fund->fund_config->custom_amount_min ?: 1),
            'max:' . currency_format($fund->fund_config->custom_amount_max ?: 2000),
        ];
    }

    /**
     * @param Fund|null $fund
     * @param string $column
     * @return array|string[]
     */
    protected function amountOptionIdRules(?Fund $fund, string $column): array
    {
        if (!$fund?->fund_config?->allow_preset_amounts) {
            return ['in:'];
        }

        return [
            Rule::exists('fund_amount_presets', $column)->where('fund_id', $fund->id),
        ];
    }

    /**
     * @param Validator $validator
     * @return void
     */
    protected function validateVoucherBackedPayout(Validator $validator): void
    {
        if (!$this->isVoucherBackedPayout()) {
            return;
        }

        $sourceFields = $this->filledBankAccountSourceKeys();

        if (count($sourceFields) === 0) {
            foreach ($this->bankAccountSourceKeys() as $field) {
                $validator->errors()->add($field, trans('validation.required', ['attribute' => $field]));
            }

            return;
        }

        if (!$this->validateSingleBankAccountSource($validator)) {
            return;
        }

        if ($validator->errors()->hasAny(['fund_id', 'voucher_id', $sourceFields[0]])) {
            return;
        }

        $sourceIdentityId = $this->bankAccountSourceIdentityId();

        if (!$sourceIdentityId) {
            $validator->errors()->add($sourceFields[0], trans('validation.in', ['attribute' => $sourceFields[0]]));

            return;
        }

        $voucher = VoucherQuery::whereEligibleForSponsorPayout(Voucher::query())
            ->whereKey((int) $this->input('voucher_id'))
            ->where('fund_id', (int) $this->input('fund_id'))
            ->where('identity_id', $sourceIdentityId)
            ->first();

        if (!$voucher) {
            $validator->errors()->add('voucher_id', trans('validation.in', ['attribute' => 'voucher_id']));

            return;
        }

        if ($validator->errors()->hasAny(['amount', 'amount_preset_id'])) {
            return;
        }

        $payoutAmount = $this->resolvePayoutAmount($this->getFundsQuery()->find((int) $this->input('fund_id')));

        if ($payoutAmount->exceeds($voucher->amount_available)) {
            $validator->errors()->add($payoutAmount->getField(), trans('validation.voucher.not_enough_funds'));
        }
    }
}
