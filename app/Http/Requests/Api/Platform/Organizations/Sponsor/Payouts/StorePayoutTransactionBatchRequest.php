<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts;

use App\Models\Organization;

/**
 * @property Organization $organization
 */
class StorePayoutTransactionBatchRequest extends StorePayoutTransactionRequest
{
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
            'upload_batch_id' => $this->uploadBatchId(),
            'payouts.*.amount' => [
                'required_without:payouts.*.amount_preset',
                ...$this->amountRules($fund),
            ],
            'payouts.*.amount_preset' => [
                'required_without:payouts.*.amount',
                ...$this->amountOptionIdRules($fund, 'amount'),
            ],
            'payouts.*.bsn' => ['nullable', ...$this->bsnRules()],
            'payouts.*.email' => ['nullable', ...$this->emailRules()],
            'payouts.*.target_iban' => $this->targetIbanRules(),
            'payouts.*.target_name' => $this->targetNameRules(),
            'payouts.*.description' => $this->descriptionRules(),
        ];
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'payouts.*.amount' => trans('validation.attributes.amount'),
            'payouts.*.email' => trans('validation.attributes.email'),
            'payouts.*.target_iban' => trans('validation.attributes.iban'),
            'payouts.*.target_name' => trans('validation.attributes.iban_name'),
            'payouts.*.description' => trans('validation.attributes.description'),
        ];
    }
}
