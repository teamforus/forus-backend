<?php

namespace App\Http\Requests\Api\Platform\Provider\Vouchers\ProductsVouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Scopes\Builders\OrganizationQuery;

class IndexProductVouchersRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{organization_id: string, per_page: 'numeric|between:1,100'}
     */
    public function rules(): array
    {
        $organizationsQuery = OrganizationQuery::whereHasPermissions(
            Organization::query(),
            $this->auth_address(),
            'scan_vouchers'
        );

        return [
            'organization_id' => 'nullable|in:' . $organizationsQuery->pluck('id')->join(','),
            'per_page' => 'numeric|between:1,100',
        ];
    }
}
