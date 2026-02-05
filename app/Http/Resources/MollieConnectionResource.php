<?php

namespace App\Http\Resources;

use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Http\Request;

/**
 * @property MollieConnection $resource
 */
class MollieConnectionResource extends BaseJsonResource
{
    public const array LOAD = [
        'organization',
    ];

    public const array LOAD_NESTED = [
        'profiles' => MollieConnectionProfileResource::class,
        'profile_active' => MollieConnectionProfileResource::class,
        'profile_pending' => MollieConnectionProfileResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|null
     */
    public function toArray(Request $request): ?array
    {
        if (is_null($connection = $this->resource)) {
            return null;
        }

        return [
            ...$connection->only([
                'id', 'city', 'street', 'country', 'postcode', 'last_name', 'first_name',
                'organization_id', 'organization_name', 'onboarding_state', 'onboarding_state_locale',
                'vat_number', 'registration_number', 'business_type',
            ]),
            'organization' => $connection->organization->only('id', 'name'),
            'profile_active' => new MollieConnectionProfileResource($connection->profile_active),
            'profile_pending' => new MollieConnectionProfileResource($connection->profile_pending),
            'profiles' => MollieConnectionProfileResource::collection($connection->profiles),
            ...$this->makeTimestamps($connection->only('created_at')),
            ...$this->makeTimestamps($connection->only('completed_at'), true),
        ];
    }
}
