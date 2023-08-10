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
        'fund.faq',
        'fund.tags',
        'fund.logo.presets',
        'fund.criteria.fund',
        'fund.criteria.fund_criterion_validators.external_validator',
        'fund.organization.logo.presets',
        'fund.organization.employees',
        'fund.organization.employees.roles.permissions',
        'fund.organization.business_type.translations',
        'fund.organization.bank_connection_active',
        'fund.organization.tags',
        'fund.fund_config.implementation',
        'fund.fund_formula_products',
        'fund.provider_organizations_approved.employees',
        'fund.tags_webshop',
        'fund.fund_formulas',
        'fund.top_up_transactions',
        'organization.offices.organization.business_type.translations',
        'organization.offices.organization.logo',
        'organization.offices.photo',
        'organization.products',
        'organization.logo',
        'organization.employees.roles.translations',
        'organization.business_type.translations',
        'fund_provider_products',
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
            'allow_products', 'allow_some_products', 'allow_budget', 'excluded',
        ]), $this->productFields($fundProvider), [
            'fund' => new FundResource($fundProvider->fund),
            'offices' => OfficeResource::collection($fundProvider->organization->offices),
            'employees' => EmployeeResource::collection($fundProvider->organization->employees),
            'organization' => array_merge((new OrganizationWithPrivateResource(
                $fundProvider->organization
            ))->toArray($request), $fundProvider->organization->only((array) 'iban')),
            'can_cancel' => !$fundProvider->hasTransactions() && !$fundProvider->isApproved() && $fundProvider->isPending(),
            'can_unsubscribe' => $fundProvider->canUnsubscribe(),
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
