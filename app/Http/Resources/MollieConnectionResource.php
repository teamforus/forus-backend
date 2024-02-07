<?php

namespace App\Http\Resources;

use App\Services\MollieService\Models\MollieConnection;

/**
 * @property MollieConnection $resource
 */
class MollieConnectionResource extends BaseJsonResource
{
    public const LOAD = [
        'profiles',
        'profile_active',
        'profile_pending',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|null
     */
    public function toArray($request): ?array
    {
        if (is_null($connection = $this->resource)) {
            return null;
        }

        return [
            ...$connection->only([
                'id', 'city', 'street', 'country', 'postcode', 'last_name', 'first_name',
                'organization_id', 'organization_name', 'onboarding_state', 'onboarding_state_locale',
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
