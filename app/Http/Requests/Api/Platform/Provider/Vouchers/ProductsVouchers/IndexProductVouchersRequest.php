<?php

namespace App\Http\Requests\Api\Platform\Provider\Vouchers\ProductsVouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Scopes\Builders\OrganizationQuery;

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
