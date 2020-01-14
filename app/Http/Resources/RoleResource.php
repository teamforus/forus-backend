<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class RoleResource
 * @property Role $resource
 * @package App\Http\Resources
 */
class RoleResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return collect($this->resource)->only([
            'id', 'key', 'name'
        ])->toArray();
    }
}
