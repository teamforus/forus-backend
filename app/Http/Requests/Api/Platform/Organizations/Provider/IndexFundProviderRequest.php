<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider;

use App\Http\Requests\BaseFormRequest;
use App\Models\FundProvider;
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
            'state'             => 'nullable|in:' . implode(',', FundProvider::STATES),
            'allow_budget'      => 'nullable|boolean',
            'allow_products'    => 'nullable|in:1,0,some',
            'per_page'          => 'nullable|numeric|min:1|max:1000',
            'fund_id'           => 'nullable|in:' . implode(',', $fundIds),
            'organization_id'   => 'nullable|in:' . implode(',', $providerIds),
            'q'                 => 'nullable|string',
            'export_format'     => 'nullable|in:csv,xls',
        ];
    }
}
