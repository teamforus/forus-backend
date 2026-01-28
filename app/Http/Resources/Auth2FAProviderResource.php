<?php

namespace App\Http\Resources;

use App\Services\Forus\Auth2FAService\Models\Auth2FAProvider;
use Illuminate\Http\Request;

/**
 * @property-read Auth2FAProvider $resource
 */
class Auth2FAProviderResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->resource->only([
            'key', 'type', 'name', 'url_ios', 'url_android',
        ]);
    }
}
