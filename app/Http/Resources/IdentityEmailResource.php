<?php

namespace App\Http\Resources;

use App\Models\IdentityEmail;

/**
 * Class IdentityEmailResource
 * @property IdentityEmail $resource
 * @package App\Http\Resources
 */
class IdentityEmailResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge($this->resource->only([
            'id', 'identity_address', 'email', 'verified', 'primary',
        ]), $this->timestamps($this->resource, 'created_at', 'updated_at'));
    }
}
