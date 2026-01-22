<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundQuery;
use App\Traits\ResolvesPayoutBankAccountPayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
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
        return Gate::allows('storePayoutsSponsor', [VoucherTransaction::class, $this->organization]);
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
            'fund_id' => $this->fundIdsRules(),
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
            'target_iban' => $this->targetIbanRules(),
            'target_name' => $this->targetNameRules(),
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
        $validator->after(fn (Validator $validator) => $this->validateSingleBankAccountSource($validator));
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
}
