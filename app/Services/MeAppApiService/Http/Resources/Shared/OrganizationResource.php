<?php

namespace App\Services\MeAppApiService\Http\Resources\Shared;

use App\Http\Resources\BaseJsonResource;
use App\Models\Organization;
use Illuminate\Http\Request;

/**
 * @property-read Organization $resource
 */
class OrganizationResource extends BaseJsonResource
{
    public const array LOAD_NESTED = [
        'logo' => MediaResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $organization = $this->resource;

        return [
            ...$organization->only([
                'id', 'name',
            ]),
            'email' => $organization->email ?: null,
            'phone' => $organization->phone ?: null,
            'logo' => $organization->logo ? new MediaResource($organization->logo) : null,
        ];
    }
}
