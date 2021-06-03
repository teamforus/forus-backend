<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BusinessTypeResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\MediaCompactResource;
use App\Http\Resources\OfficeResource;
use App\Http\Resources\OrganizationWithPrivateResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class SponsorProviderResource
 * @property Organization $resource
 * @package App\Http\Resources
 */
class SponsorProviderResource extends JsonResource
{
    public const WITH = [
        'logo',
        'funds',
        'offices.photo',
        'offices.organization.employees.roles.translations',
        'offices.organization.logo',
        'offices.organization.business_type.translations',
        'employees.roles.translations',
        'business_type.translations',
        'fund_providers',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Organization $sponsorOrganization */
        $sponsorOrganization = $request->route('organization');
        $organization = $this->resource;
        $lastActivity = $organization->getLastActivity();
        $organizationData = (new OrganizationWithPrivateResource($this->resource))->toArray($request);

        $funds = $this->getProviderFunds($sponsorOrganization, $organization);
        $productsQuery = $this->getProviderProductsQuery($organization, $funds);

        return array_merge($organizationData, [
            'logo' => new MediaCompactResource($organization->logo),
            'offices' => OfficeResource::collection($organization->offices),
            'business_type' => new BusinessTypeResource($organization->business_type),
            'employees' => EmployeeResource::collection($organization->employees),
            'products_count' => $productsQuery->count(),
            'last_activity' => $lastActivity ? $lastActivity->format('Y-m-d H:i:s') : null,
            'last_activity_locale' => $lastActivity ? $lastActivity->diffForHumans(now()) : null,
            'funds' => $funds,
            'funds_active' => $funds->filter(function (array $fund) {
                return $fund['active'];
            })->count(),
        ]);
    }

    /**
     * @param Organization $providerOrganization
     * @param Collection $funds
     * @return Builder
     */
    protected function getProviderProductsQuery(
        Organization $providerOrganization,
        Collection $funds
    ): Builder {
        $productsQuery = $providerOrganization->products()->getQuery();
        $productsQuery = ProductQuery::whereNotExpired($productsQuery);
        $productsQuery = ProductQuery::whereFundNotExcludedOrHasHistory(
            $productsQuery, $funds->pluck('id')->toArray()
        );
        $productsQuery->whereNull('sponsor_organization_id');

        return $productsQuery;
    }

    /**
     * @param Organization $sponsorOrganization
     * @param Organization $providerOrganization
     * @return Collection
     */
    protected function getProviderFunds(
        Organization $sponsorOrganization,
        Organization $providerOrganization
    ): Collection {
        $funds = FundQuery::whereHasProviderFilter(
            $sponsorOrganization->funds()->getQuery(),
            $providerOrganization->id
        )->get();

        return $funds->map(function(Fund $fund) use ($providerOrganization) {
            $fundProvider = $providerOrganization->fund_providers->where('fund_id', $fund->id);

            return array_merge($fund->only('id', 'name', 'organization_id'), [
                'active' => FundProviderQuery::whereApprovedForFundsFilter(
                    $providerOrganization->fund_providers()->getQuery(),
                    $fund->id
                )->exists(),
                'fund_provider_id' => $fundProvider->pluck('id')->first(),
            ]);
        });
    }
}
