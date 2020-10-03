<?php

namespace App\Http\Requests\Api\Platform\Provider\Vouchers\Products;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Scopes\Builders\OrganizationQuery;
use Illuminate\Validation\Rule;

class IndexProductsRequest extends BaseFormRequest
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
            'organization_id' => ['nullable', Rule::in(OrganizationQuery::whereHasPermissions(
                Organization::query(), $this->auth_address(), 'scan_vouchers'
            )->pluck('id')->toArray())],
            'per_page' => 'numeric|between:1,100',
        ];
    }
}
