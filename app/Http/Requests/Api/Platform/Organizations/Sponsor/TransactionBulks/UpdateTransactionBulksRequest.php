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
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{state: 'required|in:pending'}
     */
    public function rules(): array
    {
        return [
            'state' => 'required|in:' .  VoucherTransactionBulk::STATE_PENDING
        ];
    }
}
