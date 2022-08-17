<?php

namespace App\Http\Resources;

use App\Models\Organization;

/**
 * Class OrganizationBasicResource
 * @property Organization $resource
 * @package App\Http\Resources
 */
class OrganizationBasicWithPrivateResource extends OrganizationBasicResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $organization = $this->resource;

        return array_merge(parent::toArray($request), [
            'email' => $organization->email ?? null,
            'phone' => $organization->phone ?? null,
            'website' => $organization->website ?? null,
        ]);
    }
}
