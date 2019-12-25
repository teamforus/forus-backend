<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class OrganizationBasicResource
 * @property Organization $resource
 * @package App\Http\Resources
 */
class OrganizationWithPrivateResource extends Resource
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
            'business_type' => new BusinessTypeResource(
                $organization->business_type),
            'logo' => new MediaCompactResource(
                $organization->logo)
        ]);
    }
}
