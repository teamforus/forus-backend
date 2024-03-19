<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Role;
use App\Scopes\Builders\FundRequestQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * @property Fund $resource
 * @property ?string $stats
 */
class FundResource extends BaseJsonResource
{
    public const LOAD = [
        'faq',
        'tags',
        'logo.presets',
        'criteria.fund',
        'criteria.record_type.translation',
        'criteria.fund_criterion_validators.external_validator',
        'organization.logo.presets',
        'organization.employees',
        'organization.employees.roles.permissions',
        'organization.business_type.translations',
        'organization.bank_connection_active',
        'organization.tags',
        'fund_config.implementation',
        'fund_formula_products',
        'provider_organizations_approved.employees',
        'tags_webshop',
        'fund_formulas',
        'top_up_transactions',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return (ImplementationResource|MediaResource|OrganizationResource|\Illuminate\Database\Eloquent\Collection|\Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Support\Collection|array|bool|int|mixed|null|string)[]
     *
     * @psalm-return array{contact_info_message_default: bool|mixed|string, tags: \Illuminate\Http\Resources\Json\AnonymousResourceCollection|bool|mixed, implementation: ImplementationResource|bool|mixed, auto_validation: bool|mixed, logo: MediaResource|bool|mixed, start_date: bool|mixed|string, end_date: bool|mixed|string, start_date_locale: bool|mixed|null|string, end_date_locale: bool|mixed|null|string, organization: OrganizationResource|bool|mixed, criteria: \Illuminate\Http\Resources\Json\AnonymousResourceCollection|bool|mixed, formulas: \Illuminate\Http\Resources\Json\AnonymousResourceCollection|bool|mixed, faq: \Illuminate\Http\Resources\Json\AnonymousResourceCollection|bool|mixed, formula_products: \Illuminate\Http\Resources\Json\AnonymousResourceCollection|bool|mixed, fund_amount: bool|mixed|null|string, fund_amount_locale: bool|mixed|null|string, has_pending_fund_requests: bool|mixed, organization_funds_2fa: array|bool|mixed, parent: array|bool|mixed|null, children: \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection<array-key, array>|bool|mixed, criteria_editable?: bool|mixed, requester_count?: bool|int|mixed, backoffice?: array|bool|mixed|null,...}
     */
    public function toArray($request): array
    {
        $fund = $this->resource;
        $organization = $fund->organization;

        $baseRequest = BaseFormRequest::createFrom($request);
        $identity = $baseRequest->identity();
        $isWebShop = $baseRequest->isWebshop();
        $isDashboard = $baseRequest->isDashboard();
        $fundAmount = $fund->amountFixedByFormula();

        $fundConfigData = $this->getFundConfigData($fund);
        $financialData = $this->getFinancialData($fund, $this->stats);
        $criteriaData = $isWebShop ? $this->getCriteriaData($fund, $baseRequest) : [];
        $generatorData = $isDashboard ? $this->getVoucherGeneratorData($fund) : [];
        $prevalidationCsvData = $isDashboard ? $this->getPrevalidationCsvData($fund) : [];
        $organizationFunds2FAData = $this->organizationFunds2FAData($organization);

        $data = array_merge($fund->only([
            'id', 'name', 'description', 'description_html', 'description_short', 'description_position',
            'organization_id', 'state', 'notification_amount', 'type', 'type_locale', 'archived',
            'request_btn_text', 'external_link_text', 'external_link_url', 'faq_title', 'is_external',
            'balance_provider', 'external_page', 'external_page_url',
        ]), [
            'contact_info_message_default' => $fund->fund_config->getDefaultContactInfoMessage(),
            'tags' => TagResource::collection($fund->tags_webshop),
            'implementation' => new ImplementationResource($fund->fund_config->implementation ?? null),
            'auto_validation' => $fund->isAutoValidatingRequests(),
            'logo' => new MediaResource($fund->logo),
            'start_date' => $fund->start_date->format('Y-m-d'),
            'end_date' => $fund->end_date->format('Y-m-d'),
            'start_date_locale' => format_date_locale($fund->start_date),
            'end_date_locale' => format_date_locale($fund->end_date),
            'organization' => new OrganizationResource($organization),
            'criteria' => FundCriterionResource::collection($fund->criteria),
            'formulas' => FundFormulaResource::collection($fund->fund_formulas),
            'faq' => FaqResource::collection($fund->faq),
            'formula_products' => FundFormulaProductResource::collection($fund->fund_formula_products),
            'fund_amount' => $fundAmount ? currency_format($fundAmount) : null,
            'fund_amount_locale' => $fundAmount ? currency_format_locale($fundAmount) : null,
            'has_pending_fund_requests' => $isWebShop && $baseRequest->auth_address() && $fund->fund_requests()->where(function (Builder $builder) {
                FundRequestQuery::wherePendingOrApprovedAndVoucherIsActive($builder, auth()->id());
            })->exists(),
            'organization_funds_2fa' => $organizationFunds2FAData,
            'parent' => $fund->parent?->only(['id', 'name']),
            'children' => $fund->children->map(fn (Fund $child) => $child->only(['id', 'name'])),
        ], $fundConfigData, $criteriaData, $financialData, $generatorData, $prevalidationCsvData);

        if ($isDashboard && $organization->identityCan($identity, ['manage_funds', 'manage_fund_texts'], false)) {
            $requesterCount = VoucherQuery::whereNotExpiredAndActive($fund->vouchers())
                ->whereNull('parent_id')
                ->count();

            $data = array_merge($data, $fund->only([
                'default_validator_employee_id', 'auto_requests_validation',
            ]), [
                'criteria_editable' => $fund->criteriaIsEditable(),
                'requester_count'  => $requesterCount,
            ]);
        }

        if ($isDashboard && $organization->identityCan($identity, 'manage_funds')) {
            $data['backoffice'] = $this->getBackofficeData($fund);
        }

        return array_merge($data, $fund->only(array_keys($this->select ?? [])));
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function getFundConfigData(Fund $fund): array
    {
        return $fund->fund_config?->only([
            'key', 'allow_fund_requests', 'allow_prevalidations', 'allow_direct_requests',
            'allow_blocking_vouchers', 'backoffice_fallback', 'is_configured',
            'email_required', 'contact_info_enabled', 'contact_info_required', 'allow_reimbursements',
            'contact_info_message_custom', 'contact_info_message_text', 'bsn_confirmation_time',
            'auth_2fa_policy', 'auth_2fa_remember_ip', 'auth_2fa_restrict_reimbursements',
            'auth_2fa_restrict_auth_sessions', 'auth_2fa_restrict_emails', 'hide_meta',
        ]) ?: [];
    }

    /**
     * @param Organization $organization
     *
     * @return (bool|string)[]
     *
     * @psalm-return array{auth_2fa_policy: string, auth_2fa_remember_ip: bool, auth_2fa_restrict_emails: bool, auth_2fa_restrict_auth_sessions: bool, auth_2fa_restrict_reimbursements: bool}
     */
    protected function organizationFunds2FAData(Organization $organization): array
    {
        return [
            'auth_2fa_policy' => $organization->auth_2fa_funds_policy,
            'auth_2fa_remember_ip' => $organization->auth_2fa_funds_remember_ip,
            'auth_2fa_restrict_emails' => $organization->auth_2fa_funds_restrict_emails,
            'auth_2fa_restrict_auth_sessions' => $organization->auth_2fa_funds_restrict_auth_sessions,
            'auth_2fa_restrict_reimbursements' => $organization->auth_2fa_funds_restrict_reimbursements,
        ];
    }

    /**
     * @param Fund $fund
     * @param BaseFormRequest $request
     * @return bool
     */
    protected function isTakenByPartner(Fund $fund, BaseFormRequest $request): bool
    {
        $identity = $request->identity();
        $hashPartnerDeny = $fund->fund_config->hash_partner_deny ?? false;

        return $identity && $hashPartnerDeny && $fund->isTakenByPartner($identity);
    }

    /**
     * @param Fund $fund
     *
     * @return (float|mixed)[]
     *
     * @psalm-return array{limit_per_voucher?: float, limit_sum_vouchers?: float,...}
     */
    public function getVoucherGeneratorData(Fund $fund): array
    {
        $isVoucherManager = Gate::allows('funds.manageVouchers', [$fund, $fund->organization]);

        return $isVoucherManager ? array_merge($fund->fund_config->only([
            'allow_direct_payments', 'allow_voucher_top_ups', 'allow_voucher_records',
            'limit_voucher_top_up_amount', 'limit_voucher_total_amount',
        ]), [
            'limit_per_voucher' => $fund->getMaxAmountPerVoucher(),
            'limit_sum_vouchers' => $fund->getMaxAmountSumVouchers(),
        ]) : [];
    }

    /**
     * @param Fund $fund
     * @param string|null $stats
     *
     * @return (array|int|mixed|null)[]
     *
     * @psalm-return array{sponsor_count?: int, provider_organizations_count?: int, provider_employees_count?: mixed, validators_count?: int, budget?: array|null, product_vouchers?: array|null}
     */
    public function getFinancialData(Fund $fund, ?string $stats = null): array
    {
        if ($stats == null) {
            return [];
        }

        if (!Gate::allows('funds.showFinances', [$fund, $fund->organization])) {
            return [];
        }

        if ($stats == 'min') {
            return [
                'budget' => [
                    'used' => currency_format($fund->budget_used),
                    'total' => currency_format($fund->budget_total),
                ]
            ];
        }

        $approvedCount = $fund->provider_organizations_approved;
        $providersEmployeeCount = $approvedCount->map(function (Organization $organization) {
            return $organization->employees->count();
        })->sum();

        $validatorsCount = $fund->organization->employees->filter(function (Employee $employee) {
            return $employee->roles->filter(function (Role $role) {
                return $role->permissions->where('key', 'validate_records')->isNotEmpty();
            });
        })->count();

        $loadBudgetStats = $stats == 'all' || $stats == 'budget';
        $loadProductVouchersStats = $stats == 'all' || $stats == 'product_vouchers';

        return [
            'sponsor_count'                 => $fund->organization->employees->count(),
            'provider_organizations_count'  => $fund->provider_organizations_approved->count(),
            'provider_employees_count'      => $providersEmployeeCount,
            'validators_count'              => $validatorsCount,
            'budget'                        => $loadBudgetStats ? $this->getVoucherData($fund, 'budget') : null,
            'product_vouchers'              => $loadProductVouchersStats ? $this->getVoucherData($fund, 'product') : null,
        ];
    }

    /**
     * @param Fund $fund
     * @param string $type
     *
     * @return (mixed|string)[]
     *
     * @psalm-return array{total?: string, validated?: string, used?: string, used_active_vouchers?: string, left?: string, transaction_costs?: string, vouchers_amount: string, vouchers_count: mixed, active_vouchers_amount: string, active_vouchers_count: mixed, inactive_vouchers_amount: string, inactive_vouchers_count: mixed, deactivated_vouchers_amount: string, deactivated_vouchers_count: mixed}
     */
    public function getVoucherData(Fund $fund, string $type): array
    {
        $details = match($type) {
            'budget' => Fund::getFundDetails($fund->budget_vouchers()->getQuery()),
            'product' => Fund::getFundDetails($fund->product_vouchers()->getQuery()),
        };

        return array_merge($type == 'budget' ? [
            'total'                         => currency_format($fund->budget_total),
            'validated'                     => currency_format($fund->budget_validated),
            'used'                          => currency_format($fund->budget_used),
            'used_active_vouchers'          => currency_format($fund->budget_used_active_vouchers),
            'left'                          => currency_format($fund->budget_left),
            'transaction_costs'             => currency_format($fund->getTransactionCosts()),
        ] : [], [
            'vouchers_amount'               => currency_format($details['vouchers_amount']),
            'vouchers_count'                => $details['vouchers_count'],
            'active_vouchers_amount'        => currency_format($details['active_amount']),
            'active_vouchers_count'         => $details['active_count'],
            'inactive_vouchers_amount'      => currency_format($details['inactive_amount']),
            'inactive_vouchers_count'       => $details['inactive_count'],
            'deactivated_vouchers_amount'   => currency_format($details['deactivated_amount']),
            'deactivated_vouchers_count'    => $details['deactivated_count'],
        ]);
    }

    /**
     * @param Fund $fund
     * @return array|null
     */
    private function getBackofficeData(Fund $fund): ?array
    {
        return $fund->fund_config?->only([
            'backoffice_enabled', 'backoffice_url',
            'backoffice_ineligible_policy', 'backoffice_ineligible_redirect_url',
            'backoffice_key', 'backoffice_certificate', 'backoffice_fallback',
        ]);
    }

    /**
     * @param Fund $fund
     *
     * @return (array|string)[]
     *
     * @psalm-return array{csv_primary_key: string, csv_required_keys: array}
     */
    protected function getPrevalidationCsvData(Fund $fund): array
    {
        return [
            'csv_primary_key' => $fund->fund_config->csv_primary_key ?? '',
            'csv_required_keys' => $fund->requiredPrevalidationKeys(),
        ];
    }

    /**
     * @param Fund $fund
     * @param BaseFormRequest $baseRequest
     *
     * @return bool[]
     *
     * @psalm-return array{taken_by_partner?: bool}
     */
    protected function getCriteriaData(Fund $fund, BaseFormRequest $baseRequest): array
    {
        return $baseRequest->get('check_criteria', false) ? [
            'taken_by_partner' => $this->isTakenByPartner($fund, $baseRequest),
        ] : [];
    }
}
