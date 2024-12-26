<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Payouts;

use App\Http\Requests\Api\Platform\Organizations\Transactions\BaseIndexTransactionsRequest;
use App\Models\Fund;
use App\Models\VoucherTransaction;
use Illuminate\Validation\Rule;

class IndexPayoutTransactionsRequest extends BaseIndexTransactionsRequest
{
    /**
     * @return array
     */
    public function rules(): array
    {
        $fundIds = $this->organization?->funds?->pluck('id')->toArray();

        return [
            'to' => 'nullable|date_format:Y-m-d',
            'from' => 'nullable|date_format:Y-m-d',

            'state' => ['nullable', Rule::in(VoucherTransaction::STATES)],

            'fund_id' => ['nullable', 'exists:funds,id', Rule::in($fundIds)],
            'fund_state' => ['nullable', Rule::in(Fund::STATES)],

            'identity_address' => 'nullable|exists:identities,address',

            'amount_min' => 'nullable|numeric|min:0',
            'amount_max' => 'nullable|numeric|min:0',

            'non_cancelable_from' => 'nullable|date_format:Y-m-d',
            'non_cancelable_to' => 'nullable|date_format:Y-m-d',

            ...$this->sortableResourceRules(columns: VoucherTransaction::SORT_BY_FIELDS),
        ];
    }
}