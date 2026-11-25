<?php

namespace App\Services\MeAppApiService\Http\Resources\Requester;

use App\Http\Resources\BaseJsonResource;
use App\Models\BusinessType;
use Illuminate\Http\Request;

/**
 * @property-read BusinessType $resource
 */
class BusinessTypeResource extends BaseJsonResource
{
    public const array LOAD = [
        'translations',
    ];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'key',
            ]),
            'name' => $this->resource->name,
        ];
    }
}
