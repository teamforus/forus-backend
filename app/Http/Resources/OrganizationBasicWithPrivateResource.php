<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class OrganizationBasicResource
 * @property Organization $resource
 * @package App\Http\Resources
 */
class OrganizationBasicWithPrivateResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $organization = $this->resource;

        return collect($organization)->only([
            'id', 'name', 'business_type_id', 'email', 'phone', 'website'
        ])->merge([
            'business_type' => $organization->business_type ? new BusinessTypeResource(
                $organization->business_type
            ) : null,
            'logo' => new MediaCompactResource(
                $organization->logo)
        ]);
    }
}
