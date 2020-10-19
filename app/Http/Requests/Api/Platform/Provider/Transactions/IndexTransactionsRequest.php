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
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return !empty($this->auth_address());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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
            'per_page'          => 'numeric|between:1,100',
        ];
    }
}
