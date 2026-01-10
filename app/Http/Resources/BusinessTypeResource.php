<?php

namespace App\Http\Resources;

use App\Models\BusinessType;
use Illuminate\Http\Request;

/**
 * @property BusinessType $resource
 */
class BusinessTypeResource extends BaseJsonResource
{
    public const array LOAD = [
        'translations',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|null
     */
    public function toArray(Request $request): ?array
    {
        return $this->resource?->only('id', 'key', 'name');
    }
}
