<?php

namespace App\Http\Resources;

use App\Models\BIConnection;

/**
 * @property-read BIConnection $resource
 */
class BIConnectionResource extends BaseJsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only('id', 'token', 'organization_id', 'auth_type'), [
            'url' => route('bi-connection'),
        ]);
    }
}
