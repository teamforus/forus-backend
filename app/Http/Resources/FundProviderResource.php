<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class FundProviderResource extends Resource
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
            'id', 'organization_id', 'fund_id', 'state'
        ])->merge([
            'fund' => new FundResource(
                $this->resource->fund
            ),
            'organization' => new OrganizationResource(
                $this->resource->organization
            ),
        ])->toArray();
    }
}
