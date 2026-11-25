<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\Small\MediaSmallResource;
use App\Models\Organization;
use App\Services\MeAppApiService\Http\Resources\MobileBaseJsonResource;
use Illuminate\Http\Request;

/**
 * @property-read Organization $resource
 */
class ProviderResource extends MobileBaseJsonResource
{
    public const array LOAD_NESTED = [
        'logo' => MediaSmallResource::class,
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
            'logo' => $provider->logo ? new MediaSmallResource($provider->logo) : null,
            'business_type' => $provider->business_type ? new BusinessTypeResource($provider->business_type) : null,
        ];
    }
}
