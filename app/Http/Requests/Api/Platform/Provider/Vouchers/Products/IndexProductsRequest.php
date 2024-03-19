<?php

namespace App\Http\Requests\Api\Platform\Provider\Vouchers\Products;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Scopes\Builders\OrganizationQuery;

/**
 * Class IndexProductsRequest
 * @package App\Http\Requests\Api\Platform\Provider\Vouchers\Products
 */
class IndexProductsRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{organization_id: string, reservable: 'nullable|boolean', per_page: 'numeric|between:1,100'}
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
            'reservable' => 'nullable|boolean',
            'per_page' => 'numeric|between:1,100',
        ];
    }
}
