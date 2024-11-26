<?php

namespace App\Http\Resources;

use App\Models\Organization;

/**
 * @property Organization $resource
 */
class OrganizationWithPrivateResource extends OrganizationResource
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
            'kvk' => $organization->kvk ?? null,
            'email' => $organization->email ?? null,
            'phone' => $organization->phone ?? null,
            'website' => $organization->website ?? null,
        ]);
    }
}
