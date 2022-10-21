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
        'logo.presets',
        'funds',
        'offices.photo.presets',
        'offices.organization.employees.roles.translations',
        'offices.organization.logo',
        'offices.organization.business_type.translations',
        'offices.schedules',
        'employees.roles.translations',
        'employees.roles.permissions',
        'employees.organization',
        'employees.identity.primary_email',
        'business_type.translations',
        'fund_providers',
        'tags',
        'bank_connection_active',
        'last_employee_session',
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
        $lastActivity = $organization->last_employee_session?->last_activity_at;
        $organizationData = (new OrganizationWithPrivateResource($this->resource))->toArray($request);

        $funds = $this->getProviderFunds($sponsorOrganization, $organization);
        $fundsIds = $funds->pluck('id')->toArray();

        return array_merge($organizationData, [
            'logo' => new MediaCompactResource($organization->logo),
            'offices' => OfficeResource::collection($organization->offices),
            'business_type' => new BusinessTypeResource($organization->business_type),
            'employees' => EmployeeResource::collection($organization->employees),
            'products_count' => $organization->providerProductsQuery($fundsIds)->count(),
            'last_activity' => $lastActivity?->format('Y-m-d H:i:s'),
            'last_activity_locale' => $lastActivity?->diffForHumans(now()),
            'funds' => $funds,
            'funds_active' => $funds->filter(function (array $fund) {
                return $fund['active'];
            })->count(),
        ]);
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
        )->where('archived', false)->get();

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
