<?php

namespace App\Http\Resources\Arr;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/**
 * @property-read array $resource
 */
class ExportFieldArrResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return Arr::only($this->resource, ['key', 'name']);
    }
}
