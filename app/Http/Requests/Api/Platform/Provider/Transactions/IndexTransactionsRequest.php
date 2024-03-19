<?php

namespace App\Http\Requests\Api\Platform\Provider\Transactions;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Validation\Rule;

class IndexTransactionsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((\Illuminate\Validation\Rules\In|string)[]|\Illuminate\Validation\Rules\In|string)[]
     *
     * @psalm-return array{organization_id: list{'nullable', \Illuminate\Validation\Rules\In}, q: 'nullable|string', state: \Illuminate\Validation\Rules\In, fund_state: \Illuminate\Validation\Rules\In, from: 'date:Y-m-d', to: 'date:Y-m-d', amount_min: 'numeric|min:0', amount_max: 'numeric|min:0', per_page: string}
     */
    public function rules(): array
    {
        return [
            'organization_id'   => ['nullable', Rule::in(OrganizationQuery::whereHasPermissions(
                Organization::query(), $this->auth_address(), 'scan_vouchers'
            )->pluck('id')->toArray())],
            'q'                 => 'nullable|string',
            'state'             => Rule::in(VoucherTransaction::STATES),
            'fund_state'        => Rule::in(Fund::STATES),
            'from'              => 'date:Y-m-d',
            'to'                => 'date:Y-m-d',
            'amount_min'        => 'numeric|min:0',
            'amount_max'        => 'numeric|min:0',
            'per_page'          => $this->perPageRule(),
        ];
    }
}
