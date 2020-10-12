<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Resources\Json\Resource;
use Illuminate\Support\Collection;

/**
 * Class OrganizationBasicResource
 * @property Organization $resource
 * @package App\Http\Resources
 */
class OrganizationBasicResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request|any  $request
     * @return array|Collection
     */
    public function toArray($request)
    {
        $organization = $this->resource;

        $privateData = [
            'email' => $organization->email_public ? $organization->email ?? null: null,
            'phone' => $organization->phone_public ? $organization->phone ?? null: null,
            'website' => $organization->website_public ? $organization->website ?? null: null,
        ];

        return array_merge($organization->only([
            'id', 'name', 'business_type_id',
            'email_public', 'phone_public', 'website_public',
        ]), (array_merge($privateData, [
            'business_type' => $organization->business_type ? new BusinessTypeResource(
                $organization->business_type
            ) : null,
            'logo' => new MediaCompactResource($organization->logo)
        ])));
    }
}
