<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\VoucherTransactionBulk;
use App\Traits\ThrottleWithMeta;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read Organization $organization
 * @property-read VoucherTransactionBulk $voucherTransactionBulk
 */
class UpdateTransactionBulksRequest extends BaseFormRequest
{
    use ThrottleWithMeta;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     * @throws \App\Exceptions\AuthorizationJsonException
     */
    public function authorize(): bool
    {
        $this->maxAttempts = env('RESET_BULKS_ATTEMPTS', 10);
        $this->decayMinutes = env('RESET_BULKS_DECAY', 10);

        $this->throttleWithKey('to_many_attempts', $this, 'voucher_transaction_bulks', 'bulk_reset');

        return Gate::allows('show', $this->organization);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'state' => 'required|in:' .  VoucherTransactionBulk::STATE_PENDING
        ];
    }
}
