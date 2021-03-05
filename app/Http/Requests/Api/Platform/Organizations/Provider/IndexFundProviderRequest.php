<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider;

use App\Http\Requests\BaseFormRequest;
use App\Scopes\Builders\OrganizationQuery;
use App\Models\Organization;

/**
 * Class IndexFundProviderRequest
 * @property-read Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Provider
 */
class IndexFundProviderRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $fundIds = $this->organization->funds()->pluck('id')->toArray();
        $providerIds = OrganizationQuery::whereIsProviderOrganization(
            Organization::query(),
            $this->organization
        )->pluck('id')->toArray();

        return [
            'dismissed'         => 'nullable|boolean',
            'allow_budget'      => 'nullable|boolean',
            'allow_products'    => 'nullable|in:1,0,some',
            'per_page'          => 'numeric|between:1,100',
            'fund_id'           => 'nullable|in:' . implode(',', $fundIds),
            'organization_id'   => 'nullable|in:' . implode(',', $providerIds),
            'q'                 => 'nullable|string',
        ];
    }
}
