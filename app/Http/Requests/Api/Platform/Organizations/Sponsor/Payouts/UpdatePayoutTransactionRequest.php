<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts;

use App\Models\VoucherTransaction;
use Illuminate\Support\Facades\Gate;

/**
 * @property VoucherTransaction $transaction_address
 */
class UpdatePayoutTransactionRequest extends StorePayoutTransactionRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('updatePayoutsSponsor', [$this->transaction_address, $this->organization]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'cancel' => 'nullable|boolean',
            'skip_transfer_delay' => 'nullable|boolean',
            'amount' => [
                'nullable',
                ...$this->amountRules($this->transaction_address->voucher->fund),
            ],
            'amount_preset_id' => [
                'nullable',
                ...$this->amountOptionIdRules($this->transaction_address->voucher->fund, 'id'),
            ],
            'target_iban' => $this->targetIbanRules(true),
            'target_name' => $this->targetNameRules(true),
            'description' => $this->descriptionRules(),
        ];
    }
}
