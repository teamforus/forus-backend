<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Organization;
use App\Models\Voucher;
use App\Scopes\Builders\VoucherQuery;
use Gate;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundResource
 * @property Fund $resource
 * @package App\Http\Resources
 */
class FundResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $fund           = $this->resource;
        $organization   = $fund->organization;
        $checkCriteria  = $request->get('check_criteria', false);

        $financialData  = $this->getFinancialData($fund);
        $generatorData  = $this->getVoucherGeneratorData($fund);

        $data = array_merge($fund->only([
            'id', 'name', 'description', 'organization_id', 'state', 'notification_amount',
            'tags', 'type',
        ]), [
            'key' => $fund->fund_config->key ?? '',
            'allow_fund_requests' => $fund->fund_config->allow_fund_requests ?? false,
            'allow_prevalidations' => $fund->fund_config->allow_prevalidations ?? false,
            'auto_validation' => $fund->isAutoValidatingRequests(),
            'logo' => new MediaResource($fund->logo),
            'start_date' => $fund->start_date->format('Y-m-d'),
            'end_date' => $fund->end_date->format('Y-m-d'),
            'start_date_locale' => format_date_locale($fund->start_date),
            'end_date_locale' => format_date_locale($fund->end_date),
            'organization' => new OrganizationResource($organization),
            'criteria' => FundCriterionResource::collection($fund->criteria),
            'formulas' => FundFormulaResource::collection($fund->fund_formulas),
            'formula_products' => $fund->fund_formula_products->pluck('product_id'),
            'fund_amount'    => $fund->amountFixedByFormula(),
            'implementation' => new ImplementationResource($fund->fund_config->implementation ?? null),
            'has_pending_fund_requests' => $fund->fund_requests()->where([
                'identity_address' => auth_address(),
                'state' => FundRequest::STATE_PENDING,
            ])->exists(),
        ], $checkCriteria ? [
            'taken_by_partner' =>
                ($fund->fund_config->hash_partner_deny ?? false) &&
                $fund->isTakenByPartner(auth_address()),
        ]: [], $financialData, $generatorData);

        if ($organization->identityCan(auth()->id(), 'manage_funds')) {
            $data = array_merge($data, $fund->only([
                'default_validator_employee_id', 'auto_requests_validation',
            ]), [
                'criteria_editable' => $fund->criteriaIsEditable(),
            ]);
        }

        if ($organization->identityCan(auth()->id(), 'validate_records')) {
            $data = array_merge($data, [
                'csv_primary_key' => $fund->fund_config->csv_primary_key ?? '',
                'csv_required_keys' => $fund->requiredPrevalidationKeys()->toArray()
            ]);
        }

        return $data;
    }

    /**
     * @param Fund $fund
     * @return array
     */
    public function getVoucherGeneratorData(Fund $fund): array
    {
        return Gate::allows('funds.manageVouchers', [$fund, $fund->organization]) ? [
            'limit_per_voucher' => $fund->getMaxAmountPerVoucher(),
            'limit_sum_vouchers' => $fund->getMaxAmountSumVouchers(),
        ] : [];
    }
    /**
     * @param Fund $fund
     * @return array
     */
    public function getFinancialData(Fund $fund): array
    {
        $approvedCount = $fund->provider_organizations_approved;
        $providersEmployeeCount = $approvedCount->map(function (Organization $organization) {
            return $organization->employees->count();
        })->sum();

        $validatorsCount = $fund->organization->employeesWithPermissionsQuery([
            'validate_records'
        ])->count();

        return Gate::allows('funds.showFinances', [$fund, $fund->organization]) ? [
            'sponsor_count'                 => $fund->organization->employees->count(),
            'provider_organizations_count'  => $fund->provider_organizations_approved->count(),
            'provider_employees_count'      => $providersEmployeeCount,
            'requester_count'               => VoucherQuery::whereNotExpired(
                $fund->vouchers()->getQuery()
            )->whereNull(
                'parent_id'
            )->where(
                'state', Voucher::STATE_ACTIVE
            )->count(),
            'validators_count'              => $validatorsCount,
            'budget'                        => $this->getBudgetData($fund),
        ] : [];
    }

    /**
     * @param Fund $fund
     * @return array
     */
    public function getBudgetData(Fund $fund): array {
        return [
            'total'     => currency_format($fund->budget_total),
            'validated' => currency_format($fund->budget_validated),
            'used'      => currency_format($fund->budget_used),
            'left'      => currency_format($fund->budget_left),
            'reserved'  => round(VoucherQuery::whereNotExpired(
                $fund->budget_vouchers()->getQuery()
            )->where(
                'state', Voucher::STATE_ACTIVE
            )->sum('amount'), 2)
        ];
    }
}
