<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;

/**
 * @property Role $resource
 */
class RoleResource extends BaseJsonResource
{
    public const array LOAD = [
        'translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return $this->resource->only('id', 'key', 'name', 'description');
    }
}
