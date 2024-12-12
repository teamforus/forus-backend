<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Identity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Fund;
use App\Models\Organization;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

/**
 * @property Organization $resource
 */
class OrganizationResource extends JsonResource
{
    public const array DEPENDENCIES = [
        'logo',
        'funds',
        'offices',
        'permissions',
        'funds_count',
        'business_type',
        'bank_connection_active',
        'employees.roles.permissions',
    ];

    /**
     * @param null $request
     * @return array
     */
    public static function load($request = null): array
    {
        $load = [
            'tags',
            'offices',
            'contacts',
            'offices',
            'business_type',
            'reservation_fields',
            'bank_connection_active',
            'employees.roles.permissions',
        ];

        self::isRequested('logo', $request) && array_push($load, 'logo');
        self::isRequested('funds', $request) && array_push($load, 'funds');
        self::isRequested('business_type', $request) && array_push($load, 'business_type.translations');

        return array_merge($load, $request?->isProviderDashboard() ? [
            'mollie_connection',
            'fund_providers_allowed_extra_payments',
        ] : []);
    }

    public static function isRequested(string $key, $request = null): bool
    {
        return api_dependency_requested($key, $request);
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $baseRequest = BaseFormRequest::createFrom($request);
        $organization = $this->resource;

        $fundsDep = api_dependency_requested('funds', $request, false);
        $fundsCountDep = api_dependency_requested('funds_count', $request, false);
        $permissionsCountDep = api_dependency_requested('permissions', $request, $baseRequest->isDashboard());

        $ownerData = $baseRequest->isDashboard() ? $this->ownerData($organization) : [];
        $biConnectionData = $baseRequest->isDashboard() ? $this->getBIConnectionData($organization) : [];
        $extraPaymentsData = $baseRequest->isProviderDashboard() ? $this->getExtraPaymentsData($organization) : [];
        $privateData = $this->privateData($organization);
        $employeeOnlyData = $baseRequest->isDashboard() ? $this->employeeOnlyData($baseRequest, $organization) : [];
        $funds2FAOnlyData = $baseRequest->isDashboard() ? $this->funds2FAOnlyData($organization) : [];
        $permissionsData = $permissionsCountDep ? $this->getIdentityPermissions($organization, $baseRequest->identity()) : null;

        return array_filter(array_merge($organization->only([
            'id', 'identity_address', 'name', 'business_type_id',
            'email_public', 'phone_public', 'website_public',
            'description', 'description_html', 'reservation_phone',
            'reservation_address', 'reservation_birth_date',
        ]), $privateData, $ownerData, $biConnectionData, $employeeOnlyData, $funds2FAOnlyData, $extraPaymentsData, [
            'tags' => TagResource::collection($organization->tags),
            'logo' => new MediaResource($organization->logo),
            'business_type' => new BusinessTypeResource($organization->business_type),
            'funds' => $fundsDep ? $organization->funds->map(fn(Fund $fund) => $fund->only('id', 'name')) : '_null_',
            'funds_count' => $fundsCountDep ? $organization->funds_count : '_null_',
            'permissions' => is_array($permissionsData) ? $permissionsData : '_null_',
            'offices_count' => $organization->offices->count(),
        ]), static function ($item) {
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
        return $request->identity() && $organization->isEmployee($request->identity(), false) ? [
            'has_bank_connection' => !empty($organization->bank_connection_active),
            ...$organization->only([
                'manage_provider_products', 'backoffice_available',
                'reservations_auto_accept', 'allow_custom_fund_notifications',
                'reservations_budget_enabled', 'reservations_subsidy_enabled',
                'is_sponsor', 'is_provider', 'is_validator', 'bsn_enabled',
                'allow_batch_reservations', 'allow_budget_fund_limits',
                'allow_manual_bulk_processing', 'allow_fund_request_record_edit', 'allow_bi_connection',
                'auth_2fa_policy', 'auth_2fa_remember_ip', 'allow_2fa_restrictions',
                'allow_provider_extra_payments', 'allow_pre_checks', 'allow_payouts', 'allow_profiles',
                'allow_product_updates',
            ]),
            ...$request->isProviderDashboard() ? [
                'allow_extra_payments_by_sponsor' => $organization->canUseExtraPaymentsAsProvider(),
                'can_receive_extra_payments' => $organization->canReceiveExtraPayments(),
                'can_view_provider_extra_payments' => $organization->canViewExtraPaymentsAsProvider(),
            ] : [],
            'bank_statement_details' => $organization->only([
                'bank_transaction_id', 'bank_transaction_date', 'bank_transaction_time',
                'bank_branch_number', 'bank_branch_id', 'bank_branch_name', 'bank_fund_name',
                'bank_note', 'bank_reservation_number', 'bank_separator',
            ]),
        ] : [];
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function funds2FAOnlyData(Organization $organization): array
    {
        return $organization->only([
            'auth_2fa_funds_policy', 'auth_2fa_funds_remember_ip', 'auth_2fa_funds_restrict_emails',
            'auth_2fa_funds_restrict_auth_sessions', 'auth_2fa_funds_restrict_reimbursements',
            'auth_2fa_restrict_bi_connections',
        ]);
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function privateData(Organization $organization): array
    {
        return [
            'email' => $organization->email_public ? $organization->email ?? null : null,
            'phone' => $organization->phone_public ? $organization->phone ?? null : null,
            'website' => $organization->website_public ? $organization->website ?? null : null,
        ];
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function ownerData(Organization $organization): array
    {
        $canUpdate = Gate::allows('update', $organization);

        return $canUpdate ? array_merge($organization->only([
            'kvk', 'iban', 'btw', 'phone', 'email', 'website', 'email_public',
            'phone_public', 'website_public',
        ]), [
            'contacts' => OrganizationContactResource::collection($organization->contacts),
            'reservation_fields' => OrganizationReservationFieldResource::collection($organization->reservation_fields),
        ]) : [];
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function getBIConnectionData(Organization $organization): array
    {
        return $organization->allow_bi_connection ? [
            'bi_connection_url' => route('biConnection')
        ] : [];
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function getExtraPaymentsData(Organization $organization): array
    {
        $canUpdate = Gate::allows('allowExtraPayments', [MollieConnection::class, $organization]);

        return $canUpdate ? $organization->only([
            'reservation_allow_extra_payments',
        ]) : [];
    }
}
