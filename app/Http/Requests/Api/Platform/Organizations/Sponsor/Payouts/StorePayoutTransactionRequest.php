<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Rules\Base\IbanNameRule;
use App\Rules\Base\IbanRule;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\FundRequestQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

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
        $useBankAccount = (bool) $this->input('fund_request_id');

        return [
            'fund_id' => $this->fundIdsRules(),
            'fund_request_id' => $this->fundRequestIdRules(),
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
    protected function fundRequestIdRules(): array
    {
        return [
            'nullable',
            'integer',
            function (string $attribute, mixed $value, callable $fail) {
                if (!$value) {
                    return;
                }

                $fundRequest = FundRequestQuery::whereHasPayoutBankAccountRecordsForOrganization(
                    FundRequest::query(),
                    $this->organization,
                )->find((int) $value);

                $fundRequest?->loadMissing(['records', 'fund.fund_config']);

                if (!$fundRequest?->getIban(false) || !$fundRequest?->getIbanName(false)) {
                    $fail(trans('validation.in', ['attribute' => $attribute]));
                    return;
                }

                $this->fundRequest = $fundRequest;
            },
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
     * @return FundRequest|null
     */
    public function fundRequest(): ?FundRequest
    {
        if ($this->fundRequest instanceof FundRequest) {
            return $this->fundRequest;
        }

        $fundRequestId = $this->input('fund_request_id');

        return $fundRequestId ? ($this->fundRequest = FundRequest::find((int) $fundRequestId)) : null;
    }
}
