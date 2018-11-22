<?php

namespace App\Http\Resources;

use App\Models\Fund;
use Carbon\Carbon;
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

        return collect($this->resource)->only([
            'id', 'name', 'organization_id',
            'state'
        ])->merge([
            'key' => $fund->fund_config ? $fund->fund_config->key : '',
            'logo' => new MediaResource($this->resource->logo),
            'start_date' => (new Carbon(
                $this->resource->start_date
            ))->format('Y-m-d'),
            'end_date' => (new Carbon(
                $this->resource->end_date
            ))->format('Y-m-d'),
            'organization' => new OrganizationResource(
                $this->resource->organization
            ),
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
            })
        ])->merge($ownerData)->toArray();
    }
}
