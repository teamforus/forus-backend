<?php

namespace App\Traits;

use App\Models\FundRequest;
use App\Models\ProfileBankAccount;
use App\Models\Reimbursement;
use App\Models\VoucherTransaction;
use App\Rules\Base\IbanNameRule;
use App\Rules\Base\IbanRule;
use App\Searches\Sponsor\PayoutBankAccounts\FundRequestPayoutBankAccountSearch;
use App\Searches\Sponsor\PayoutBankAccounts\PayoutTransactionPayoutBankAccountSearch;
use App\Searches\Sponsor\PayoutBankAccounts\ProfilePayoutBankAccountSearch;
use App\Searches\Sponsor\PayoutBankAccounts\ReimbursementPayoutBankAccountSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

trait ResolvesPayoutBankAccountPayload
{
    /**
     * @return array
     */
    public function bankAccountData(): array
    {
        return Arr::except($this->bankAccountPayload(), ['source_identity_id']);
    }

    /**
     * @return int|null
     */
    public function bankAccountSourceIdentityId(): ?int
    {
        return $this->bankAccountPayload()['source_identity_id'] ?? null;
    }

    /**
     * @return array
     */
    public function bankAccountPayload(): array
    {
        return $this->resolveBankAccountPayload($this->bankAccountSources());
    }

    /**
     * @param array $sources
     * @return array
     */
    protected function resolveBankAccountPayload(array $sources): array
    {
        foreach ($sources as $inputKey => $config) {
            if ($id = $this->input($inputKey)) {
                $model = $config['getModel']((int) $id);

                if ($config['loadMissing'] && $model) {
                    $model->loadMissing($config['loadMissing']);
                }

                if (!$model) {
                    throw ValidationException::withMessages([
                        $inputKey => [trans('validation.in', ['attribute' => $inputKey])],
                    ]);
                }

                return $this->filterBankAccountPayload([
                    'target_iban' => $config['getIban']($model),
                    'target_name' => $config['getName']($model),
                    'target_source_type' => $config['type'] ?? null,
                    'target_source_id' => $config['type'] ? (int) $id : null,
                    'source_identity_id' => $config['getIdentityId']($model),
                ]);
            }
        }

        return $this->filterBankAccountPayload([
            'target_iban' => $this->input('target_iban'),
            'target_name' => $this->input('target_name'),
        ]);
    }

    /**
     * @param array $payload
     * @return array
     */
    protected function filterBankAccountPayload(array $payload): array
    {
        return array_filter($payload, static fn ($value) => $value !== null);
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
        return $this->bankAccountIdRules(
            (new FundRequestPayoutBankAccountSearch($this->organization, $this->bankAccountSearchFilters()))->query()
        );
    }

    /**
     * @return array
     */
    protected function profileBankAccountIdRules(): array
    {
        return $this->bankAccountIdRules(
            (new ProfilePayoutBankAccountSearch($this->organization, $this->bankAccountSearchFilters()))->query()
        );
    }

    /**
     * @return array
     */
    protected function reimbursementIdRules(): array
    {
        return $this->bankAccountIdRules(
            (new ReimbursementPayoutBankAccountSearch($this->organization, $this->bankAccountSearchFilters()))->query()
        );
    }

    /**
     * @return array
     */
    protected function payoutTransactionIdRules(): array
    {
        return $this->bankAccountIdRules(
            (new PayoutTransactionPayoutBankAccountSearch($this->organization, $this->bankAccountSearchFilters()))->query()
        );
    }

    /**
     * @return array
     */
    protected function bankAccountSources(): array
    {
        return $this->baseBankAccountSources();
    }

    /**
     * @return array
     */
    protected function baseBankAccountSources(): array
    {
        $filters = $this->bankAccountSearchFilters();

        return [
            'fund_request_id' => [
                'type' => 'fund_request',
                'getModel' => fn ($id) => (new FundRequestPayoutBankAccountSearch($this->organization, $filters))
                    ->query()
                    ->find($id),
                'getIban' => fn (FundRequest $model) => $model->getIban(false),
                'getName' => fn (FundRequest $model) => $model->getIbanName(false),
                'getIdentityId' => fn (FundRequest $model) => $model->identity_id,
                'loadMissing' => ['records', 'fund.fund_config'],
            ],
            'profile_bank_account_id' => [
                'type' => 'profile_bank_account',
                'getModel' => fn ($id) => (new ProfilePayoutBankAccountSearch($this->organization, $filters))
                    ->query()
                    ->find($id),
                'getIban' => fn (ProfileBankAccount $model) => $model->iban,
                'getName' => fn (ProfileBankAccount $model) => $model->name,
                'getIdentityId' => fn (ProfileBankAccount $model) => $model->profile?->identity_id,
                'loadMissing' => ['profile'],
            ],
            'reimbursement_id' => [
                'type' => 'reimbursement',
                'getModel' => fn ($id) => (new ReimbursementPayoutBankAccountSearch($this->organization, $filters))
                    ->query()
                    ->find($id),
                'getIban' => fn (Reimbursement $model) => $model->iban,
                'getName' => fn (Reimbursement $model) => $model->iban_name,
                'getIdentityId' => fn (Reimbursement $model) => $model->voucher?->identity_id,
                'loadMissing' => ['voucher'],
            ],
            'payout_transaction_id' => [
                'type' => 'voucher_transaction',
                'getModel' => fn ($id) => (new PayoutTransactionPayoutBankAccountSearch($this->organization, $filters))
                    ->query()
                    ->find($id),
                'getIban' => fn (VoucherTransaction $model) => $model->target_iban,
                'getName' => fn (VoucherTransaction $model) => $model->target_name,
                'getIdentityId' => fn (VoucherTransaction $model) => $model->voucher?->identity_id,
                'loadMissing' => ['voucher'],
            ],
        ];
    }

    /**
     * @param Validator $validator
     * @return bool
     */
    protected function validateSingleBankAccountSource(Validator $validator): bool
    {
        $filledFields = $this->filledBankAccountSourceKeys();

        if (count($filledFields) <= 1) {
            return true;
        }

        foreach ($filledFields as $field) {
            $validator->errors()->add($field, trans('validation.prohibited'));
        }

        return false;
    }

    /**
     * @return string[]
     */
    protected function filledBankAccountSourceKeys(): array
    {
        return array_values(array_filter($this->bankAccountSourceKeys(), fn (string $field) => $this->filled($field)));
    }

    /**
     * @param bool $nullable
     * @param bool $batch
     * @return array
     */
    protected function targetIbanRules(bool $nullable = false, bool $batch = false): array
    {
        $fields = implode(',', $this->bankAccountSourceKeys());

        return [
            $batch ? 'required' : ($nullable ? 'nullable' : "required_without_all:$fields"),
            new IbanRule(),
        ];
    }

    /**
     * @param bool $nullable
     * @param bool $batch
     * @return array
     */
    protected function targetNameRules(bool $nullable = false, bool $batch = false): array
    {
        $fields = implode(',', $this->bankAccountSourceKeys());

        return [
            'required_with:target_iban',
            $batch ? 'required' : ($nullable ? 'nullable' : "required_without_all:$fields"),
            new IbanNameRule(),
        ];
    }

    /**
     * @return array
     */
    protected function bankAccountSourceKeys(): array
    {
        return array_keys($this->bankAccountSources());
    }

    /**
     * @return array
     */
    protected function bankAccountSearchFilters(): array
    {
        return [];
    }
}
