<?php

namespace App\Http\Resources;

use App\Models\Fund;
use Carbon\Carbon;
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

        return collect($this->resource)->only([
            'id', 'name', 'organization_id',
            'state'
        ])->merge([
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
            'validators' => ValidatorResource::collection(
                $this->resource->organization->validators
            ),
            'criteria' => FundCriterionResource::collection(
                $this->resource->criteria
            ),
            'budget' => [
                'total' => currency_format($fund->budget_total),
                'validated' => currency_format($fund->budget_validated),
                'used' => currency_format($fund->budget_used)
            ]
        ])->toArray();
    }
}
