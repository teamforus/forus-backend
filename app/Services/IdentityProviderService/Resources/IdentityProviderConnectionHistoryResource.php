<?php

namespace App\Services\IdentityProviderService\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use Illuminate\Http\Request;

/**
 * @property-read IdentityProviderConnection $resource
 */
class IdentityProviderConnectionHistoryResource extends BaseJsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only(['uid', 'tenant_id', 'status']),
            ...$this->makeTimestamps($this->resource->only(['consented_at', 'disconnected_at'])),
        ];
    }
}
