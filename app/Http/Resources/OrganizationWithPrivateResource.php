<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;

/**
 * @property Organization $resource
 */
class OrganizationWithPrivateResource extends OrganizationResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
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
