<?php

namespace App\Http\Resources;

use App\Models\FundProvider;
use App\Scopes\Builders\ProductQuery;
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
    public function toArray($request): array
    {
        $fundProvider = $this->resource;

        return collect($fundProvider)->only([
            'id', 'organization_id', 'fund_id', 'dismissed',
            'allow_products', 'allow_some_products', 'allow_budget'
        ])->merge([
            'products' => $fundProvider->fund_provider_products->pluck('product_id'),
            'products_count_all'    => $fundProvider->organization->products->count(),
            'products_count_approved' => ProductQuery::approvedForFundsAndActiveFilter(
                $fundProvider->organization->products()->getQuery(),
                $fundProvider->fund_id
            )->count(),
            'fund'                  => new FundResource($fundProvider->fund),
            'organization'          => array_merge((new OrganizationWithPrivateResource(
                $fundProvider->organization
            ))->toArray($request), [
                'iban' => $fundProvider->organization->iban
            ]),
            'employees' => EmployeeResource::collection(
                $fundProvider->organization->employees
            ),
        ])->toArray();
    }
}
