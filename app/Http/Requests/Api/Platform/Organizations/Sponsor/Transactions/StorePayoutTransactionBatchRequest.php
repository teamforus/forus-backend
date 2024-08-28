<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Transactions;

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
            'payouts.*.note' => $this->noteRules(),
            'payouts.*.amount' => [
                'required_without:payouts.*.amount_preset',
                ...$this->amountRules($fund),
            ],
            'payouts.*.amount_preset' => [
                'required_without:payouts.*.amount',
                ...$this->amountOptionIdRules($fund, 'amount'),
            ],
            'payouts.*.target_iban' => $this->targetIbanRules(),
            'payouts.*.target_name' => $this->targetNameRules(),
        ];
    }
}
