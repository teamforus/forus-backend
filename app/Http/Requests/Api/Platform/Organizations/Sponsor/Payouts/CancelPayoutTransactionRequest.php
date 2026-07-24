<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Support\Facades\Gate;

/**
 * @property Organization $organization
 * @property VoucherTransaction $transaction_address
 */
class CancelPayoutTransactionRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('cancelPayoutsSponsor', [$this->transaction_address, $this->organization]);
    }
}
