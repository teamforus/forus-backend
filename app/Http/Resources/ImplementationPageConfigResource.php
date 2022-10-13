<?php

namespace App\Http\Resources;

use App\Models\ImplementationPageConfig;

/**
 * @property ImplementationPageConfig $resource
 */
class ImplementationPageConfigResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return $this->resource->only('id', 'page_key', 'page_config_key', 'is_active');
    }
}