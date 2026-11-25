<?php

namespace App\Http\Requests\Api\Platform\Provider\Transactions;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Database\Query\Builder as QueryBuilder;
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
            'organization_id' => [
                'nullable',
                'integer',
                Rule::exists('organizations', 'id')->where(function (QueryBuilder $query): void {
                    $identityAddress = $this->auth_address();

                    $query->whereIn('id', $identityAddress
                        ? OrganizationQuery::queryForVoucherScanning($identityAddress)->select('organizations.id')
                        : []);
                }),
            ],
            'q' => 'nullable|string',
            'state' => Rule::in(VoucherTransaction::STATES),
            'fund_state' => Rule::in(Fund::STATES),
            'from' => 'date:Y-m-d',
            'to' => 'date:Y-m-d',
            'amount_min' => 'numeric|min:0',
            'amount_max' => 'numeric|min:0',
            'per_page' => $this->perPageRule(),
            ...$this->orderByRules(...array_diff(VoucherTransaction::SORT_BY_FIELDS, ['payment_type', 'relation'])),
        ];
    }
}
