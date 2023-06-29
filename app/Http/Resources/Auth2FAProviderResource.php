<?php

namespace App\Http\Resources;

use App\Services\Forus\Auth2FAService\Models\Auth2FAProvider;

/**
 * @property-read Auth2FAProvider $resource
 */
class Auth2FAProviderResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only([
            'key', 'type', 'name', 'url_ios', 'url_android',
        ]);
    }
}
