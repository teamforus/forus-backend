<?php

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @property Organization $resource
 */
class OrganizationBasicResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Collection
     */
    public function toArray(Request $request): ?array
    {
        if (!$organization = $this->resource) {
            return null;
        }

        return [
            ...$organization->only([
                'id', 'name', 'business_type_id', 'email_public', 'phone_public', 'website_public',
            ]),
            'email' => $organization->email_public ? $organization->email ?? null : null,
            'phone' => $organization->phone_public ? $organization->phone ?? null : null,
            'website' => $organization->website_public ? $organization->website ?? null : null,
            'logo' => new MediaCompactResource($organization->logo),
            'business_type' => new BusinessTypeResource($organization->business_type),
        ];
    }
}
