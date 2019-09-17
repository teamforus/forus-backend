<?php

namespace App\Http\Resources;

use App\Models\FundProvider;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundProviderResource
 * @property FundProvider $resource
 * @package App\Http\Resources
 */
class FundProviderResource extends Resource
{
    public static $load = [
        'fund.logo.sizes',
        'fund.providers',
        'fund.top_up_transactions',
        'fund.provider_organizations_approved.employees',
        'organization.logo.sizes',
        'organization.business_type.translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return collect($this->resource)->only([
            'id', 'organization_id', 'fund_id', 'state'
        ])->merge([
            'fund' => new FundResource(
                $this->resource->fund
            ),
            'organization' => new OrganizationResource(
                $this->resource->organization
            ),
        ])->toArray();
    }
}
