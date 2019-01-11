<?php

namespace App\Http\Resources;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use Gate;
use Illuminate\Http\Resources\Json\Resource;

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
        /** @var Fund $fund */
        $fund = $this->resource;

        $ownerData = [];

        if (Gate::allows('funds.showFinances', [
            $fund, $fund->organization
        ])) {
            $ownerData['budget'] = [
                'total' => currency_format($fund->budget_total),
                'validated' => currency_format($fund->budget_validated),
                'used' => currency_format($fund->budget_used)
            ];

            $ownerData['providers_count'] = $fund->provider_organizations_approved()->count();
            $ownerData['validators_count'] = $this->resource->organization->validators->count();
        }

        $sponsorCount = $fund->organization->employees->count() + 1;

        $providers = $fund->providers()->where([
            'state' => 'approved'
        ])->get();

        $providerCount = $providers->map(function ($fundProvider){
            /** @var FundProvider $fundProvider */
            return $fundProvider->organization->employees->count() + 1;
        })->sum();

        if ($fund->state == 'active'){
            $requesterCount = $fund->vouchers()->whereNull('parent_id')->count();
        } else {
            $requesterCount = 0;
        }

        /** @var Organization $organization */
        $organization = $this->resource->organization;

        return collect($this->resource)->only([
            'id', 'name', 'organization_id',
            'state'
        ])->merge($organization->identityCan(auth()->id(), [
            'validate_records'
        ]) ? [
            'csv_primary_key' => $fund->fund_config ? $fund->fund_config->csv_primary_key : '',
            'csv_required_keys' => $fund->requiredPrevalidationKeys()
        ] : []
        )->merge([
            'key' => $fund->fund_config ? $fund->fund_config->key : '',
            'logo' => new MediaResource($this->resource->logo),
            'start_date' => $this->resource->start_date->format('Y-m-d'),
            'end_date' => $this->resource->end_date->format('Y-m-d'),
            'start_date_locale' => format_date_locale($this->resource->start_date),
            'end_date_locale' => format_date_locale($this->resource->end_date),
            'organization' => new OrganizationResource($organization),
            'product_categories' => ProductCategoryResource::collection(
                $this->resource->product_categories
            ),
            'criteria' => FundCriterionResource::collection(
                $this->resource->criteria
            ),
            'validators' => collect(
                $this->resource->organization->validators
            )->map(function($validator) {
                return collect($validator)->only([
                    'identity_address'
                ]);
            }),
            'sponsor_count' => $sponsorCount,
            'provider_count' => $providerCount,
            'requester_count' => $requesterCount,
            'fund_amount' => $fund->amountFixedByFormula()
        ])->merge($ownerData)->toArray();
    }
}
