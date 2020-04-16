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
        'fund.logo.presets',
        'fund.providers',
        'fund.organization',
        'fund.employees',
        'fund.top_up_transactions',
        'fund.provider_organizations_approved.employees',
        'organization.products',
        'organization.logo.presets',
        'organization.business_type.translations',
        'fund_provider_products'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $fundProvider = $this->resource;

        return collect($fundProvider)->only([
            'id', 'organization_id', 'fund_id', 'dismissed',
            'allow_products', 'allow_some_products', 'allow_budget'
        ])->merge([
            'products' => $fundProvider->fund_provider_products
                ->pluck('product_id'),
            'products_count_all'    => $fundProvider->organization->products->count(),
            'fund'                  => new FundResource($fundProvider->fund),
            'organization'          => new OrganizationWithPrivateResource(
                $fundProvider->organization
            ),
            'employees' => EmployeeResource::collection(
                $fundProvider->organization->employees
            ),
        ])->toArray();
    }
}
