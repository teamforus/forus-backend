<?php

namespace App\Http\Resources;

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
            'budget' => [
                'total' => 60000,
                'validated' => 40000,
                'used' => 20000
            ]
        ])->toArray();
    }
}
