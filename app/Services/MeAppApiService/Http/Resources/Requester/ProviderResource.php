<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Models\Organization;
use App\Services\MeAppApiService\Http\Resources\Shared\MediaResource;
use Illuminate\Http\Request;

/**
 * @property-read Organization $resource
 */
class ProviderResource extends BaseJsonResource
{
    public const array LOAD_NESTED = [
        'logo' => MediaResource::class,
        'business_type' => BusinessTypeResource::class,
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $provider = $this->resource;

        return [
            ...$provider->only([
                'id', 'name',
            ]),
            'email' => $provider->email_public ? $provider->email : null,
            'phone' => $provider->phone_public ? $provider->phone : null,
            'logo' => $provider->logo ? new MediaResource($provider->logo) : null,
            'business_type' => $provider->business_type ? new BusinessTypeResource($provider->business_type) : null,
        ];
    }
}
