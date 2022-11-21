<?php

namespace App\Http\Resources;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\Organization;
use App\Scopes\Builders\FundRequestQuery;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * @property Fund $resource
 */
class FundResource extends BaseJsonResource
{
    public const LOAD = [
        'faq',
        'tags',
        'criteria.fund',
        'organization.logo',
        'organization.employees',
        'organization.business_type',
        'fund_config.implementation',
        'provider_organizations_approved.employees',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $fund = $this->resource;
        $organization = $fund->organization;
        $checkCriteria = $request->get('check_criteria', false);

        $baseRequest = BaseFormRequest::createFrom($request);
        $isWebshop = $baseRequest->isWebshop();
        $isDashboard = $baseRequest->isDashboard();

        $financialData = $isDashboard ? $this->getFinancialData($fund) : [];
        $generatorData = $isDashboard ? $this->getVoucherGeneratorData($fund) : [];

        $data = array_merge($fund->only([
            'id', 'name', 'description', 'description_html', 'description_short',
            'organization_id', 'state', 'notification_amount', 'type', 'type_locale', 'archived',
            'request_btn_text', 'external_link_text', 'external_link_url', 'faq_title', 'is_external',
            'balance_provider',
        ]), $fund->fund_config->only([
            'key', 'allow_fund_requests', 'allow_prevalidations', 'allow_direct_requests',
            'allow_blocking_vouchers', 'backoffice_fallback', 'is_configured',
            'email_required', 'contact_info_enabled', 'contact_info_required', 'allow_reimbursements',
            'contact_info_message_custom', 'contact_info_message_text', 'bsn_confirmation_time',
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
            'formula_products' => $fund->fund_formula_products->pluck('product_id'),
            'fund_amount' => $fund->amountFixedByFormula(),
            'has_pending_fund_requests' => $baseRequest->auth_address() && $fund->fund_requests()->where(function (Builder $builder) {
                FundRequestQuery::wherePendingOrApprovedAndVoucherIsActive($builder, auth()->id());
            })->exists(),
        ], $isWebshop && $checkCriteria ? [
            'taken_by_partner' => $this->isTakenByPartner($fund, $baseRequest),
        ]: [], $financialData, $generatorData);

        if ($isDashboard && $organization->identityCan($baseRequest->identity(), 'manage_funds')) {
            $data = array_merge($data, $fund->only([
                'default_validator_employee_id', 'auto_requests_validation',
            ]), [
                'criteria_editable' => $fund->criteriaIsEditable(),
            ]);

            $data['backoffice'] = $this->getBackofficeData($fund);
        }

        if ($isDashboard && $organization->identityCan($baseRequest->identity(), 'validate_records')) {
            $data = array_merge($data, [
                'csv_primary_key' => $fund->fund_config->csv_primary_key ?? '',
                'csv_required_keys' => $fund->requiredPrevalidationKeys()->toArray()
            ]);
        }

        return $data;
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

        return $isVoucherManager ? array_merge($fund->fund_config->only([
            'allow_direct_payments', 'allow_voucher_top_ups',
            'limit_voucher_top_up_amount', 'limit_voucher_total_amount',
        ]), [
            'limit_per_voucher' => currency_format($fund->getMaxAmountPerVoucher()),
            'limit_sum_vouchers' => currency_format($fund->getMaxAmountSumVouchers()),
        ]) : [];
    }
    /**
     * @param Fund $fund
     * @return array
     */
    public function getFinancialData(Fund $fund): array
    {
        if (!Gate::allows('funds.showFinances', [$fund, $fund->organization])) {
            return [];
        }

        $approvedCount = $fund->provider_organizations_approved;
        $providersEmployeeCount = $approvedCount->map(function (Organization $organization) {
            return $organization->employees->count();
        })->sum();

        $validatorsCount = $fund->organization->employeesWithPermissionsQuery([
            'validate_records'
        ])->count();

        $requesterCount = VoucherQuery::whereNotExpiredAndActive(
            $fund->vouchers()->getQuery()
        )->whereNull('parent_id')->count();

        return [
            'sponsor_count'                 => $fund->organization->employees->count(),
            'provider_organizations_count'  => $fund->provider_organizations_approved->count(),
            'provider_employees_count'      => $providersEmployeeCount,
            'validators_count'              => $validatorsCount,
            'requester_count'               => $requesterCount,
            'budget'                        => $this->getBudgetData($fund),
        ];
    }

    /**
     * @param Fund $fund
     * @return array
     */
    public function getBudgetData(Fund $fund): array {
        $details = Fund::getFundDetails($fund->budget_vouchers()->getQuery());
        $reservedQuery = $fund->budget_vouchers()->getQuery();
        $reservedAmount = VoucherQuery::whereNotExpiredAndActive($reservedQuery)->sum('amount');

        return [
            'total'                         => currency_format($fund->budget_total),
            'validated'                     => currency_format($fund->budget_validated),
            'used'                          => currency_format($fund->budget_used),
            'used_active_vouchers'          => currency_format($fund->budget_used_active_vouchers),
            'left'                          => currency_format($fund->budget_left),
            'transaction_costs'             => currency_format($fund->getTransactionCosts()),
            'reserved'                      => round($reservedAmount, 2),
            'vouchers_amount'               => currency_format($details['vouchers_amount']),
            'vouchers_count'                => $details['vouchers_count'],
            'active_vouchers_amount'        => currency_format($details['active_amount']),
            'active_vouchers_count'         => $details['active_count'],
            'inactive_vouchers_amount'      => currency_format($details['inactive_amount']),
            'inactive_vouchers_count'       => $details['inactive_count'],
            'deactivated_vouchers_amount'   => currency_format($details['deactivated_amount']),
            'deactivated_vouchers_count'    => $details['deactivated_count'],
        ];
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
}
