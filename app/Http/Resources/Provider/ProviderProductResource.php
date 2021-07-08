<?php

namespace App\Http\Resources\Provider;

use App\Http\Resources\OrganizationBasicResource;
use App\Http\Resources\ProductResource;
use App\Models\Fund;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ProductResource
 * @package App\Http\Resources
 */
class ProviderProductResource extends ProductResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request|any $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'sponsor_organization_id' => $this->resource->sponsor_organization_id,
            'sponsor_organization' => new OrganizationBasicResource($this->resource->sponsor_organization),
            'excluded_funds' => Fund::whereHas('providers.product_exclusions', function(Builder $builder) {
                $builder->where('product_id', '=', $this->resource->id);
                $builder->orWhereNull('product_id');
            })->select([
                'id', 'name', 'state'
            ])->get(),
        ]);
    }
}
