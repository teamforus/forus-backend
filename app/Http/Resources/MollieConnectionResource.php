<?php

namespace App\Http\Resources;

use App\Services\MollieService\Models\MollieConnection;

/**
 * @property MollieConnection $resource
 */
class MollieConnectionResource extends BaseJsonResource
{
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

        return array_merge($connection->only([
            'id', 'city', 'street', 'country', 'postcode', 'last_name', 'first_name',
            'organization_id', 'organization_name', 'onboarding_state', 'onboarding_state_locale',
        ]), [
            'organization' => $connection->organization->only('id', 'name'),
            'active_profile' => new MollieConnectionProfileResource($connection->active_profile),
            'pending_profile' => new MollieConnectionProfileResource($connection->pending_profile),
        ],
            $this->timestamps($connection, 'created_at'),
            $this->makeTimestamps($connection->only(['completed_at']), true)
        );
    }
}
