<?php

namespace App\Http\Resources;

use App\Models\Identity2FA;
use Illuminate\Http\Request;

/**
 * @property-read Identity2FA $resource
 */
class Identity2FAResource extends BaseJsonResource
{
    public const array LOAD = [];

    public const array LOAD_NESTED = [
        'auth_2fa_provider' => Auth2FAProviderResource::class,
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $data = array_merge($this->resource->only([
            'uuid', 'state',
        ]), [
            'provider_type' => new Auth2FAProviderResource($this->resource->auth_2fa_provider),
        ]);

        if ($this->resource->isTypePhone()) {
            $data = array_merge($data, [
                'phone' => $this->resource->phone_masked,
            ]);
        }

        if ($this->resource->isTypeAuthenticator() && $this->resource->isPending()) {
            $data = array_merge($data, $this->resource->only(['secret', 'secret_url']));
        }

        return array_merge($data, $this->makeTimestamps($this->resource->only('created_at'), true));
    }
}
