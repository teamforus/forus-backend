<?php

namespace App\Http\Resources;

use App\Services\OpenIdService\Models\OpenIdFlow;
use Illuminate\Http\Request;

/**
 * @property-read OpenIdFlow $resource
 */
class OpenIdFlowResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return ?array
     * @property OpenIdFlow $resource
     */
    public function toArray(Request $request): ?array
    {
        return $this->resource?->only([
            'id', 'provider', 'key', 'name',
        ]);
    }
}
