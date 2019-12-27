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
class ProviderResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|Collection
     */
    public function toArray($request)
    {
        $organization = $this->resource;

        return collect($organization)->only([
            'id', 'name', 'business_type_id',
            $organization->email_public ? 'email': '',
            $organization->phone_public ? 'phone': '',
            $organization->website_public ? 'website': ''
        ])->merge([
            'business_type' => new BusinessTypeResource(
                $organization->business_type
            ),
            'logo' => $organization->logo ? new MediaCompactResource(
                $organization->logo
            ) : null,
            'offices' => OfficeResource::collection(
                $organization->offices
            )
        ]);
    }
}
