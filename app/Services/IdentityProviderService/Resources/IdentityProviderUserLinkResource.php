<?php

namespace App\Services\IdentityProviderService\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use Illuminate\Http\Request;

/**
 * @property-read IdentityProviderMembership $resource
 */
class IdentityProviderUserLinkResource extends BaseJsonResource
{
    public const array LOAD = [
        'connection.organization',
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->resource->uid,
            'provider' => $this->resource->connection->provider,
            'organization' => $this->resource->connection->organization->only(['id', 'name']),
            'tenant_id' => $this->resource->connection->tenant_id,
            'claim_state' => $this->resource->claim_state,
            ...$this->makeTimestamps($this->resource->only(['consented_at'])),
        ];
    }
}
