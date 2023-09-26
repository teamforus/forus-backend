<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\BusinessTypeResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\MediaCompactResource;
use App\Http\Resources\OfficeResource;
use App\Http\Resources\OrganizationWithPrivateResource;
use App\Http\Resources\Tiny\OrganizationTinyResource;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property Organization $resource
 * @property Organization $sponsor_organization
 */
class SponsorProviderResource extends BaseJsonResource
{
    public const LOAD = [
        'tags',
        'funds',
        'logo.presets',
        'fund_providers.fund',
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
        $provider = $this->resource;

        if ($this->resource_type == 'select') {
            return (new OrganizationTinyResource($provider))->toArray($request);
        }

        $fundsData = $this->fundApprovalDetails($provider);
        $providerData = (new OrganizationWithPrivateResource($this->resource))->toArray($request);
        $lastActivity = $provider->last_employee_session?->last_activity_at;

        return array_merge($providerData, $fundsData, [
            'logo' => new MediaCompactResource($provider->logo),
            'offices' => OfficeResource::collection($provider->offices),
            'business_type' => new BusinessTypeResource($provider->business_type),
            'employees' => EmployeeResource::collection($provider->employees),
            'last_activity' => $lastActivity?->format('Y-m-d H:i:s'),
            'last_activity_locale' => $lastActivity?->diffForHumans(now()),
        ]);
    }

    /**
     * @param Organization $provider
     * @return array
     */
    protected function fundApprovalDetails(Organization $provider): array
    {
        $fundProviders = $this->getFundProviders($this->sponsor_organization, $provider);
        $fundsIds = $fundProviders->pluck('fund_id')->toArray();

        return [
            'fund_providers' => $fundProviders->paginate(10),
            'products_count' => $provider->providerProductsQuery($fundsIds)->count(),
        ];
    }

    /**
     * @param Organization $sponsorOrganization
     * @param Organization $providerOrganization
     * @return HasMany
     */
    protected function getFundProviders(
        Organization $sponsorOrganization,
        Organization $providerOrganization
    ): HasMany {
        return $providerOrganization->fund_providers()->whereHas(
            'fund', function (Builder $builder) use ($sponsorOrganization) {
            $builder->where([
                'organization_id' => $sponsorOrganization->id,
                'archived' => false,
            ]);
        })->with('fund:id,name,organization_id');
    }
}
