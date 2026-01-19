<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts;

use App\Http\Requests\BaseIndexFormRequest;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Support\Facades\Gate;

/**
 * @property Organization $organization
 */
class IndexPayoutBankAccountsRequest extends BaseIndexFormRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'per_page' => $this->perPageRule(1000),
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('viewAnyPayoutBankAccountsSponsor', [VoucherTransaction::class, $this->organization]);
    }
}
