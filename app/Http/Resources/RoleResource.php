<?php

namespace App\Http\Resources;

use App\Models\Role;

/**
 * Class RoleResource
 * @property Role $resource
 * @package App\Http\Resources
 */
class RoleResource extends BaseJsonResource
{
    public const LOAD = [
        'translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return $this->resource->only('id', 'key', 'name', 'description');
    }
}
