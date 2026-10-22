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
     * @return array{
     *   id: int, name: string,
     *   email: string|null, phone: string|null,
     *   logo: MediaResource|null
     * }
     */
    public function toArray(Request $request): array
    {
        $organization = $this->resource;

        return [
            ...$organization->only([
                'id', 'name',
            ]),
            /** @var string|null */
            'email' => $organization->email ?? null,
            /** @var string|null */
            'phone' => $organization->phone ?? null,
            'logo' => $organization->logo ? new MediaResource($organization->logo) : null,
        ];
    }
}
