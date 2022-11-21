<?php

namespace App\Http\Resources;

use App\Models\FundProvider;
use App\Scopes\Builders\ProductQuery;

/**
 * @property FundProvider $resource
 */
class FundProviderResource extends BaseJsonResource
{
    public const LOAD = [
        'fund.logo.presets',
        'fund.providers',
        'fund.organization.logo',
        'fund.organization.business_type.translations',
        'fund.employees',
        'fund.top_up_transactions',
        'fund.provider_organizations_approved.employees',
        'organization.offices.organization.business_type.translations',
        'organization.offices.organization.logo',
        'organization.offices.photo',
        'organization.products',
        'organization.logo',
        'organization.employees.roles.translations',
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
        $lastActivity = $fundProvider->getLastActivity();

        return array_merge($fundProvider->only([
            'id', 'organization_id', 'fund_id', 'state', 'state_locale',
            'allow_products', 'allow_some_products', 'allow_budget',
        ]), $this->productFields($fundProvider), [
            'fund' => new FundResource($fundProvider->fund),
            'offices' => OfficeResource::collection($fundProvider->organization->offices),
            'employees' => EmployeeResource::collection($fundProvider->organization->employees),
            'organization' => array_merge((new OrganizationWithPrivateResource(
                $fundProvider->organization
            ))->toArray($request), $fundProvider->organization->only((array) 'iban')),
            'cancelable' => !$fundProvider->hasTransactions() && !$fundProvider->isApproved() && $fundProvider->isPending(),
            'last_activity' => $lastActivity?->format('Y-m-d H:i:s'),
            'last_activity_locale' => $lastActivity?->diffForHumans(now()),
        ]);
    }

    /**
     * @param FundProvider $fundProvider
     * @return array
     */
    private function productFields(FundProvider $fundProvider): array
    {
        return [
            'products' => $fundProvider->fund_provider_products->pluck('product_id'),
            'products_count_all' => $fundProvider->organization->products->count(),
            'products_count_available' => ProductQuery::whereFundNotExcludedOrHasHistory(
                $fundProvider->organization->products()->getQuery(),
                $fundProvider->fund_id
            )->count(),
            'products_count_approved' => ProductQuery::approvedForFundsAndActiveFilter(
                $fundProvider->organization->products()->getQuery(),
                $fundProvider->fund_id
            )->count(),
        ];
    }
}
