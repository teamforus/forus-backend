<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts;

use App\Http\Requests\BaseFormRequest;
use App\Models\Data\BankAccount;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Rules\Base\IbanNameRule;
use App\Rules\Base\IbanRule;
use App\Scopes\Builders\FundQuery;
use App\Searches\Sponsor\PayoutBankAccounts\FundRequestPayoutBankAccountSearch;
use App\Searches\Sponsor\PayoutBankAccounts\PayoutTransactionPayoutBankAccountSearch;
use App\Searches\Sponsor\PayoutBankAccounts\ProfilePayoutBankAccountSearch;
use App\Searches\Sponsor\PayoutBankAccounts\ReimbursementPayoutBankAccountSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * @property Organization $organization
 */
class StorePayoutTransactionRequest extends BaseFormRequest
{
    protected ?FundRequest $fundRequest = null;

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
        $useBankAccount = $this->hasBankAccountId();

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
            'target_iban' => $this->targetIbanRules($useBankAccount),
            'target_name' => $this->targetNameRules($useBankAccount),
            'bsn' => ['nullable', ...$this->bsnRules()],
            'email' => ['nullable', ...$this->emailRules()],
            'description' => $this->descriptionRules(),
        ];
    }

    /**
     * @return FundRequest|null
     */
    public function fundRequest(): ?FundRequest
    {
        if ($this->fundRequest) {
            return $this->fundRequest;
        }

        $fundRequestId = $this->input('fund_request_id');

        return $fundRequestId ? ($this->fundRequest = FundRequest::find((int) $fundRequestId)) : null;
    }

    /**
     * @return BankAccount
     */
    public function bankAccount(): BankAccount
    {
        $sources = [
            'fund_request_id' => [
                'getModel' => fn ($id) => $this->fundRequest(),
                'getIban' => fn ($model) => $model->getIban(false),
                'getName' => fn ($model) => $model->getIbanName(false),
                'loadMissing' => ['records', 'fund.fund_config'],
                'type' => 'Fund request',
            ],
            'profile_bank_account_id' => [
                'getModel' => fn ($id) => ProfilePayoutBankAccountSearch::queryForOrganization($this->organization)->find($id),
                'getIban' => fn ($model) => $model->iban,
                'getName' => fn ($model) => $model->name,
                'loadMissing' => null,
                'type' => 'Profile bank account',
            ],
            'reimbursement_id' => [
                'getModel' => fn ($id) => ReimbursementPayoutBankAccountSearch::queryForOrganization($this->organization)->find($id),
                'getIban' => fn ($model) => $model->iban,
                'getName' => fn ($model) => $model->iban_name,
                'loadMissing' => null,
                'type' => 'Reimbursement',
            ],
            'payout_transaction_id' => [
                'getModel' => fn ($id) => PayoutTransactionPayoutBankAccountSearch::queryForOrganization($this->organization)->find($id),
                'getIban' => fn ($model) => $model->target_iban,
                'getName' => fn ($model) => $model->target_name,
                'loadMissing' => null,
                'type' => 'Payout transaction',
            ],
        ];

        foreach ($sources as $inputKey => $config) {
            if ($id = $this->input($inputKey)) {
                $model = $config['getModel']($id);

                if ($config['loadMissing'] && $model) {
                    $model->loadMissing($config['loadMissing']);
                }

                if (!$model) {
                    throw ValidationException::withMessages([
                        $inputKey => [trans('validation.in', ['attribute' => $inputKey])],
                    ]);
                }

                return new BankAccount(
                    $config['getIban']($model),
                    $config['getName']($model),
                );
            }
        }

        return new BankAccount(
            $this->input('target_iban'),
            $this->input('target_name'),
        );
    }

    /**
     * @return bool
     */
    protected function hasBankAccountId(): bool
    {
        return $this->input('fund_request_id') ||
            $this->input('profile_bank_account_id') ||
            $this->input('reimbursement_id') ||
            $this->input('payout_transaction_id');
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
     * @param bool $nullable
     * @return array
     */
    protected function targetIbanRules(bool $nullable = false): array
    {
        return [
            $nullable ? 'nullable' : 'required',
            new IbanRule(),
        ];
    }

    /**
     * @param bool $nullable
     * @return string[]
     */
    protected function targetNameRules(bool $nullable = false): array
    {
        return [
            $nullable ? 'nullable' : 'required',
            new IbanNameRule(),
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
     * @param Builder|Relation $search
     * @return array
     */
    protected function bankAccountIdRules(Builder|Relation $search): array
    {
        return [
            'nullable',
            'integer',
            function (string $attribute, mixed $value, callable $fail) use ($search) {
                if (!$value) {
                    return;
                }

                if (!$search->find((int) $value)) {
                    $fail(trans('validation.in', ['attribute' => $attribute]));
                }
            },
        ];
    }

    /**
     * @return array
     */
    protected function fundRequestIdRules(): array
    {
        return $this->bankAccountIdRules(FundRequestPayoutBankAccountSearch::queryForOrganization($this->organization));
    }

    /**
     * @return array
     */
    protected function profileBankAccountIdRules(): array
    {
        return $this->bankAccountIdRules(ProfilePayoutBankAccountSearch::queryForOrganization($this->organization));
    }

    /**
     * @return array
     */
    protected function reimbursementIdRules(): array
    {
        return $this->bankAccountIdRules(ReimbursementPayoutBankAccountSearch::queryForOrganization($this->organization));
    }

    /**
     * @return array
     */
    protected function payoutTransactionIdRules(): array
    {
        return $this->bankAccountIdRules(PayoutTransactionPayoutBankAccountSearch::queryForOrganization($this->organization));
    }
}
