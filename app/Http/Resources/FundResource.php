<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Voucher;
use App\Scopes\Builders\FundRequestQuery;
use App\Scopes\Builders\VoucherQuery;
use App\Statistics\Funds\FinancialOverviewStatistic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @property Fund $resource
 * @property ?string $stats
 * @property ?int $year
 */
class FundResource extends BaseJsonResource
{
    public const LOAD = [
        'faq',
        'tags',
        'parent',
        'children',
        'logo.presets',
        'criteria.fund',
        'criteria.fund_criterion_rules',
        'criteria.record_type.translation',
        'criteria.record_type.record_type_options',
        'organization.tags',
        'organization.offices',
        'organization.contacts',
        'organization.logo.presets',
        'organization.reservation_fields',
        'organization.bank_connection_active',
        'organization.business_type.translations',
        'organization.employees.roles.permissions',
        'fund_config.implementation.page_provider',
        'fund_formula_products',
        'provider_organizations_approved.employees',
        'tags_webshop',
        'fund_formulas.record_type.translations',
        'fund_formulas.fund.fund_config.implementation',
        'top_up_transactions',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $fund = $this->resource;
        $organization = $fund->organization;

        $baseRequest = BaseFormRequest::createFrom($request);
        $identity = $baseRequest->identity();
        $isWebShop = $baseRequest->isWebshop();
        $isDashboard = $baseRequest->isDashboard();
        $fundAmount = $fund->amountFixedByFormula();
        $loadStats = $this->stats && Gate::allows('funds.showFinances', [$fund, $fund->organization]);

        $fundConfigData = $this->getFundConfigData($fund, $isDashboard);

        $financialData = $loadStats ? FinancialOverviewStatistic::getFinancialData($fund, $this->stats, $this->year ?: now()->year) : [];
        $generatorData = $isDashboard ? $this->getVoucherGeneratorData($fund) : [];
        $prevalidationCsvData = $isDashboard ? $this->getPrevalidationCsvData($fund) : [];
        $organizationFunds2FAData = $this->organizationFunds2FAData($organization);

        $data = array_merge($fund->only([
            'id', 'name', 'description', 'description_html', 'description_short', 'description_position',
            'organization_id', 'state', 'notification_amount', 'type', 'type_locale', 'archived',
            'request_btn_text', 'external_link_text', 'external_link_url', 'faq_title', 'is_external',
            'balance_provider', 'external_page', 'external_page_url',
        ]), [
            'outcome_type' => $fund->fund_config?->outcome_type ?: FundConfig::OUTCOME_TYPE_VOUCHER,
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
            'criteria_steps' => FundCriteriaStepResource::collection($fund->criteria_steps->sortBy('order')),
            'faq' => FaqResource::collection($fund->faq),
            'formulas' => FundFormulaResource::collection($fund->fund_formulas),
            'formula_products' => FundFormulaProductResource::collection($fund->fund_formula_products),
            'fund_amount' => $fundAmount ? currency_format($fundAmount) : null,
            'fund_amount_locale' => $fundAmount ? currency_format_locale($fundAmount) : null,
            'organization_funds_2fa' => $organizationFunds2FAData,
            'parent' => $fund->parent?->only(['id', 'name']),
            'children' => $fund->children->map(fn (Fund $child) => $child->only(['id', 'name'])),
        ], $fundConfigData, $financialData, $generatorData, $prevalidationCsvData);

        if ($isDashboard && $organization->identityCan($identity, [Permission::MANAGE_FUNDS, Permission::MANAGE_FUND_TEXTS], false)) {
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

        if ($isDashboard && $organization->identityCan($identity, Permission::MANAGE_FUNDS)) {
            $data['backoffice'] = $this->getBackofficeData($fund);
        }

        if ($isDashboard && $organization->identityCan($identity, [
            Permission::MANAGE_FUNDS, Permission::VALIDATE_RECORDS, Permission::MANAGE_PAYOUTS,
        ], false)) {
            $data['amount_presets'] = FundAmountPresetResource::collection($fund->amount_presets);
        }

        if ($isWebShop) {
            $data['received'] = $this->fundReceived($baseRequest->identity(), $fund);
            $data['has_pending_fund_requests'] = $this->hadPendingRequests($baseRequest->identity(), $fund);
            $data = [...$data, ...$this->getCriteriaData($fund, $baseRequest)];
        }

        return array_merge($data, $fund->only(array_keys($this->select ?? [])));
    }

    /**
     * @param Identity|null $identity
     * @param Fund $fund
     * @return bool
     */
    protected function hadPendingRequests(?Identity $identity, Fund $fund): bool
    {
        return $identity && $fund->fund_requests()->where(function (Builder $builder) use ($identity) {
            FundRequestQuery::wherePendingOrApprovedAndVoucherIsActive($builder, $identity->id);
        })->exists();
    }

    /**
     * @param Identity|null $identity
     * @param Fund $fund
     * @return bool
     */
    protected function fundReceived(?Identity $identity, Fund $fund): bool
    {
        return $identity && $fund->vouchers()->where(function (Builder|Voucher $builder) use ($identity) {
            VoucherQuery::whereNotExpiredAndActive($builder);
            $builder->where('identity_id', $identity->id);
        })->exists();
    }

    /**
     * @param Fund $fund
     * @param bool $isDashboard
     * @return array
     */
    protected function getFundConfigData(Fund $fund, bool $isDashboard): array
    {
        return [
            ...$fund->fund_config ? $fund->fund_config->only([
                'key', 'allow_fund_requests', 'allow_prevalidations', 'allow_direct_requests',
                'allow_blocking_vouchers', 'backoffice_fallback', 'is_configured',
                'email_required', 'contact_info_enabled', 'contact_info_required', 'allow_reimbursements',
                'contact_info_message_custom', 'contact_info_message_text', 'bsn_confirmation_time',
                'auth_2fa_policy', 'auth_2fa_remember_ip', 'auth_2fa_restrict_reimbursements',
                'auth_2fa_restrict_auth_sessions', 'auth_2fa_restrict_emails',
                'hide_meta', 'voucher_amount_visible', 'provider_products_required',
                'help_enabled', 'help_title', 'help_block_text', 'help_button_text',
                'help_email', 'help_phone', 'help_website', 'help_chat', 'help_description',
                'help_show_email', 'help_show_phone', 'help_show_website', 'help_show_chat',
                'help_description_html', 'criteria_label_requirement_show',
                'pre_check_excluded', 'pre_check_note',
            ]) : [],
            ...$isDashboard && $fund->fund_config ? $fund->fund_config->only([
                'allow_custom_amounts', 'allow_preset_amounts',
                'allow_custom_amounts_validator', 'allow_preset_amounts_validator',
                'custom_amount_min', 'custom_amount_max',
            ]) : [],
        ];
    }

    /**
     * @param Organization $organization
     * @return array
     */
    protected function organizationFunds2FAData(Organization $organization): array
    {
        return [
            'auth_2fa_policy' => $organization->auth_2fa_funds_policy,
            'auth_2fa_remember_ip' => $organization->auth_2fa_funds_remember_ip,
            'auth_2fa_restrict_emails' => $organization->auth_2fa_funds_restrict_emails,
            'auth_2fa_restrict_auth_sessions' => $organization->auth_2fa_funds_restrict_auth_sessions,
            'auth_2fa_restrict_reimbursements' => $organization->auth_2fa_funds_restrict_reimbursements,
            'auth_2fa_restrict_bi_connections' => $organization->auth_2fa_restrict_bi_connections,
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
     * @return array
     */
    public function getVoucherGeneratorData(Fund $fund): array
    {
        $isVoucherManager = Gate::allows('funds.manageVouchers', [$fund, $fund->organization]);
        $limitPerVoucher = $fund->getMaxAmountPerVoucher();
        $limitSumVoucher = $fund->getMaxAmountSumVouchers();

        return $isVoucherManager ? array_merge($fund->fund_config->only([
            'allow_direct_payments', 'allow_voucher_top_ups', 'allow_voucher_records',
            'limit_voucher_top_up_amount', 'limit_voucher_total_amount',
        ]), [
            'limit_per_voucher' => currency_format($limitPerVoucher),
            'limit_per_voucher_locale' => currency_format_locale($limitPerVoucher),
            'limit_sum_vouchers' => currency_format($limitSumVoucher),
            'limit_sum_vouchers_locale' => currency_format_locale($limitSumVoucher),
        ]) : [];
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
     * @return array
     */
    protected function getPrevalidationCsvData(Fund $fund): array
    {
        return [
            'csv_primary_key' => $fund->fund_config->csv_primary_key ?? '',
            'csv_required_keys' => $fund->requiredPrevalidationKeys(false, []),
        ];
    }

    /**
     * @param Fund $fund
     * @param BaseFormRequest $baseRequest
     * @return array|bool[]
     */
    protected function getCriteriaData(Fund $fund, BaseFormRequest $baseRequest): array
    {
        return $baseRequest->get('check_criteria', false) ? [
            'taken_by_partner' => $this->isTakenByPartner($fund, $baseRequest),
        ] : [];
    }
}
