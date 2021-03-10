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
        'offices',
        'employees',
        'business_type',
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

        return array_merge($organizationData, [
            'logo' => new MediaCompactResource($organization->logo),
            'offices' => OfficeResource::collection($organization->offices),
            'business_type' => new BusinessTypeResource($organization->business_type),
            'employees' => EmployeeResource::collection($organization->employees),
            'products_count' => $organization->products_count,
            'last_activity' => $lastActivity ? $lastActivity->format('Y-m-d H:i:s') : null,
            'last_activity_locale' => $lastActivity ? $lastActivity->diffForHumans(now()) : null,
            'funds' => $funds,
            'funds_active' => $funds->filter(function (array $fund) {
                return $fund['active'];
            })->count(),
        ]);
    }

    /**
     * @param Organization $sponsorOrganization
     * @param Organization $providerOrganization
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    protected function getProviderFunds(
        Organization $sponsorOrganization,
        Organization $providerOrganization
    ) {
        return FundQuery::whereHasProviderFilter(
            $sponsorOrganization->funds()->getQuery(),
            $providerOrganization->id
        )->get()->map(function(Fund $fund) use ($providerOrganization) {
            return array_merge($fund->only('id', 'name', 'organization_id'), [
                'active' => FundProviderQuery::whereApprovedForFundsFilter(
                    $providerOrganization->fund_providers()->getQuery(),
                    $fund->id
                )->exists(),
                'fund_provider_id' => FundProviderQuery::FundsFilter(
                    $providerOrganization->fund_providers()->getQuery(),
                    $fund->id
                )->get('id')->pluck('id')->flatten(),
            ]);
        });
    }
}
