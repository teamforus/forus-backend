<?php

namespace App\Http\Requests\Api\Platform\Provider\Vouchers\ProductsVouchers;

use App\Http\Requests\BaseFormRequest;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Validation\Rule;

class IndexProductVouchersRequest extends BaseFormRequest
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
            'per_page' => 'numeric|between:1,100',
        ];
    }
}
