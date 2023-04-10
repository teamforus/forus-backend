<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Identity;
use App\Models\Permission;
use App\Models\Role;
use Gate;
use App\Models\Fund;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Organization $resource
 */
class OrganizationResource extends JsonResource
{
    public const DEPENDENCIES = [
        'logo',
        'funds',
        'funds_count',
        'business_type',
        'permissions',
        'employees.roles.permissions',
        'bank_connection_active',
        'offices',
    ];

    /**
     * @param null $request
     * @return array
     */
    public static function load($request = null): array
    {
        $load = [
            'tags',
            'bank_connection_active',
        ];

        self::isRequested('logo', $request) && array_push($load, 'logo');
        self::isRequested('funds', $request) && array_push($load, 'funds');
        self::isRequested('business_type', $request) && array_push($load, 'business_type.translations');

        return $load;
    }

    public static function isRequested(string $key, $request = null): bool
    {
        return api_dependency_requested($key, $request);
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $baseRequest = BaseFormRequest::createFrom($request);
        $organization = $this->resource;

        $fundsDep = api_dependency_requested('funds', $request, false);
        $fundsCountDep = api_dependency_requested('funds_count', $request, false);
        $permissionsCountDep = api_dependency_requested('permissions', $request, $baseRequest->isDashboard());

        $ownerData = $baseRequest->isDashboard() ? $this->ownerData($organization) : [];
        $privateData = $this->privateData($organization);
        $employeeOnlyData = $baseRequest->isDashboard() ? $this->employeeOnlyData($baseRequest, $organization) : [];
        $permissionsData = $permissionsCountDep ? $this->getIdentityPermissions($organization, $baseRequest->identity()) : null;
        
        return array_filter(array_merge($organization->only([
            'id', 'identity_address', 'name', 'kvk', 'business_type_id',
            'email_public', 'phone_public', 'website_public',
            'description', 'description_html',
        ]), $privateData, $ownerData, $employeeOnlyData, [
            'tags' => TagResource::collection($organization->tags),
            'logo' => new MediaResource($organization->logo),
            'business_type' => new BusinessTypeResource($organization->business_type),
            'funds' => $fundsDep ? $organization->funds->map(fn (Fund $fund) => $fund->only('id', 'name')) : '_null_',
            'funds_count' => $fundsCountDep ? $organization->funds_count : '_null_',
            'permissions' => is_array($permissionsData) ? $permissionsData : '_null_',
            'offices_count' => $organization->offices->count(),
        ]), static function($item) {
            return $item !== '_null_';
        });
    }

    /**
     * @param Organization $organization
     * @param Identity|null $identity
     * @return array|null
     */
    protected function getIdentityPermissions(Organization $organization, ?Identity $identity): ?array
    {
        if (!$identity) {
            return null;
        }

        if ($identity->address === $organization->identity_address) {
            return Permission::allMemCached()->pluck('key')->toArray();
        }

        $employee = $organization->employees->firstWhere('identity_address', $identity->address);

        return $employee ? array_unique($employee->roles->reduce(function (array $acc, Role $role) {
            return array_merge($acc, $role->permissions->pluck('key')->toArray());
        }, [])) : [];
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @return array
     */
    protected function employeeOnlyData(BaseFormRequest $request, Organization $organization): array
    {
        $isEmployee = $request->identity() && $organization->employees
            ->where('identity_address', $request->identity()->address)
            ->isNotEmpty();

        return $isEmployee ? array_merge([
            'has_bank_connection' => !empty($organization->bank_connection_active),
        ], $organization->only([
            'manage_provider_products', 'backoffice_available', 'reservations_auto_accept',
            'allow_custom_fund_notifications', 'validator_auto_accept_funds',
            'reservations_budget_enabled', 'reservations_subsidy_enabled',
            'is_sponsor', 'is_provider', 'is_validator', 'bsn_enabled',
            'allow_batch_reservations', 'allow_budget_fund_limits', 'allow_manual_bulk_processing',
        ])) : [];
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function privateData(Organization $organization): array
    {
        return [
            'email' => $organization->email_public ? $organization->email ?? null: null,
            'phone' => $organization->phone_public ? $organization->phone ?? null: null,
            'website' => $organization->website_public ? $organization->website ?? null: null,
        ];
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function ownerData(Organization $organization): array
    {
        $canUpdate = Gate::allows('organizations.update', $organization);

        return $canUpdate ? $organization->only([
            'iban', 'btw', 'phone', 'email', 'website', 'email_public',
            'phone_public', 'website_public',
        ]) : [];
    }
}
