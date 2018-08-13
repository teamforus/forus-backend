<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class ValidatorResource extends Resource
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
            'id', 'identity_address', 'organization_id'
        ])->merge([
            'organization' => new OrganizationResource(
                $this->resource->organization
            )
        ])->toArray();
    }
}
