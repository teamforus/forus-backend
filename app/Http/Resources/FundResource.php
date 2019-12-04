<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\Organization;
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
    public function toArray($request)
    {
        $fund                   = $this->resource;
        $organization           = $fund->organization;
        $validators             = $organization->validators;
        $sponsorCount           = $organization->employees->count() + 1;

        $providersEmployeeCount = $fund->provider_organizations_approved;
        $providersEmployeeCount = $providersEmployeeCount->reduce(function (
            int $carry,
            Organization $organization
        ) {
            return $carry + ($organization->employees->count() + 1);
        }, 0);

        if ($fund->state == Fund::STATE_ACTIVE) {
            $requesterCount = $fund->vouchers->where(
                'parent_id', '=', null
            )->count();
        } else {
            $requesterCount = 0;
        }

        if (Gate::allows('funds.showFinances', [$fund, $organization])) {
            $financialData = [
                'sponsor_count'             => $sponsorCount,
                'provider_employees_count'  => $providersEmployeeCount,
                'requester_count'           => $requesterCount,
                'validators_count'          => $validators->count(),
                'budget'                    => [
                    'total'     => currency_format($fund->budget_total),
                    'validated' => currency_format($fund->budget_validated),
                    'used'      => currency_format($fund->budget_used),
                    'left'      => currency_format($fund->budget_left)
                ]
            ];
        } else {
            $financialData = [];
        }

        $data = array_merge($fund->only([
            'id', 'name', 'organization_id', 'state', 'notification_amount', 'tags'
        ]), [
            'key' => $fund->fund_config->key ?? '',
            'logo' => new MediaResource($fund->logo),
            'start_date' => $fund->start_date->format('Y-m-d'),
            'end_date' => $fund->end_date->format('Y-m-d'),
            'start_date_locale' => format_date_locale($fund->start_date),
            'end_date_locale' => format_date_locale($fund->end_date),
            'organization' => new OrganizationResource($organization),
            'product_categories' => ProductCategoryResource::collection(
                $fund->product_categories
            ),
            'criteria' => FundCriterionResource::collection(
                $fund->criteria
            ),
            'formulas' => FundFormulaResource::collection(
                $fund->fund_formulas
            ),
            'formula_products' => $fund->fund_formula_products->pluck(
                'product_id'
            ),
            'validators' => $validators->map(function($validator) {
                return collect($validator)->only([
                    'identity_address'
                ]);
            }),
            'fund_amount' => $fund->amountFixedByFormula()
        ], $financialData);

        if ($organization->identityCan(auth()->id(), 'validate_records')) {
            $data = array_merge($data, [
                'csv_primary_key' => $fund->fund_config->csv_primary_key ?? '',
                'csv_required_keys' => $fund->requiredPrevalidationKeys()
            ]);
        }

        return $data;
    }
}
