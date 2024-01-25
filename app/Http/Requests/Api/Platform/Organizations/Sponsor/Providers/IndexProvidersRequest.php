<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers;

use App\Http\Requests\Api\Platform\Organizations\Provider\IndexFundProviderRequest;
use App\Models\Organization;

/**
 * @property-read Organization $organization
 */
class IndexProvidersRequest extends IndexFundProviderRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $funds = $this->organization->funds->pluck('id');
        $implementations = $this->organization->implementations->pluck('id');

        return array_merge(parent::rules(), [
            'from'                      => 'nullable|date_format:Y-m-d',
            'to'                        => 'nullable|date_format:Y-m-d',
            'fund_ids'                  => 'nullable|array',
            'fund_ids.*'                => 'required|exists:funds,id|in:' . $funds->join(','),
            'postcodes'                 => 'nullable|array',
            'postcodes.*'               => 'nullable|string|max:100',
            'provider_ids'              => 'nullable|array',
            'provider_ids.*'            => 'nullable|exists:organizations,id',
            'product_category_ids'      => 'nullable|array',
            'product_category_ids.*'    => 'nullable|exists:product_categories,id',
            'order_by'                  => 'nullable|in:name,application_date',
            'order_dir'                 => 'nullable|in:asc,desc',
            'per_page'                  => $this->perPageRule(1000),
            'resource_type'             => $this->resourceTypeRule(['default', 'select']),
            'business_type_ids'         => 'nullable|array',
            'business_type_ids.*'       => 'nullable|exists:business_types,id',
            'implementation_id'         => 'nullable|exists:implementations,id|in:' . $implementations->join(','),
        ]);
    }
}
