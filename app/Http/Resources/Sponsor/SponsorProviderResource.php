<?php

namespace App\Http\Resources\Sponsor;

use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\BusinessTypeResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\MediaCompactResource;
use App\Http\Resources\OfficeResource;
use App\Http\Resources\OrganizationWithPrivateResource;
use App\Http\Resources\Tiny\OrganizationTinyResource;
use App\Http\Resources\TagResource;
use App\Models\FundProvider;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * @property Organization $resource
 * @property Organization $sponsor_organization
 */
class SponsorProviderResource extends BaseJsonResource
{
    public const array LOAD = [
        'funds',
        'fund_providers.fund',
        'bank_connection_active',
        'last_employee_session',
    ];

    public const array LOAD_NESTED = [
        'tags' => TagResource::class,
        'logo' => MediaCompactResource::class,
        'offices' => OfficeResource::class,
        'employees' => EmployeeResource::class,
        'business_type' => BusinessTypeResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
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
        $funds = $this->getProviderFunds($this->sponsor_organization, $provider);
        $fundsIds = $funds->pluck('id')->toArray();

        return [
            'funds' => $funds,
            'products_count' => $provider->providerProductsQuery($fundsIds)->count(),
        ];
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
        $fund_providers = $providerOrganization->fund_providers
            ->filter(fn (FundProvider $provider) => $provider->fund->organization_id == $sponsorOrganization->id)
            ->filter(fn (FundProvider $provider) => $provider->fund->archived == false)
            ->values();

        return $fund_providers->map(fn (FundProvider $provider) => array_merge($provider->fund->only([
            'id', 'name', 'organization_id',
        ]), [
            'fund_provider_id' => $provider->id,
            'fund_provider_state' => $provider->state,
            'fund_provider_state_locale' => $provider->state_locale,
        ]))->values();
    }
}
