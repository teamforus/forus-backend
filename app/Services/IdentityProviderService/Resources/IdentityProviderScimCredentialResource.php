<?php

namespace App\Services\IdentityProviderService\Resources;

use App\Http\Resources\BaseJsonResource;
use App\Services\IdentityProviderService\Models\IdentityProviderScimCredential;
use Illuminate\Http\Request;

/**
 * @property-read IdentityProviderScimCredential $resource
 */
class IdentityProviderScimCredentialResource extends BaseJsonResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'uid' => $this->resource->uid,
            ...$this->makeTimestamps($this->resource->only(['created_at', 'revoked_at', 'last_used_at'])),
        ];
    }
}
