<?php

namespace App\Http\Resources\Arr;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * @property-read array $resource
 */
class ExportFieldArrResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return Arr::only($this->resource, ['key', 'name']);
    }
}
