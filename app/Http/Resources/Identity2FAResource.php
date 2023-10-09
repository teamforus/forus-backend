<?php

namespace App\Http\Resources;

use App\Models\Identity2FA;

/**
 * @property-read Identity2FA $resource
 */
class Identity2FAResource extends BaseJsonResource
{
    /**
     * @param $request
     * @return array
     */
    public function toArray($request): array
    {
        $data = array_merge($this->resource->only([
            'uuid', 'state',
        ]), [
            'provider_type' => new Auth2FAProviderResource($this->resource->auth_2fa_provider),
        ]);

        if ($this->resource->isTypePhone()) {
            $data = array_merge($data, $this->resource->only(['phone']));
        }

        if ($this->resource->isTypeAuthenticator() && $this->resource->isPending()) {
            $data = array_merge($data, $this->resource->only(['secret', 'secret_url']));
        }

        return array_merge($data, $this->makeTimestamps($this->resource->only('created_at'), true));
    }
}
