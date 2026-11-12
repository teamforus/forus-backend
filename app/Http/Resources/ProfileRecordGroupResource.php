<?php

namespace App\Http\Resources;

use App\Models\ProfileRecordGroup;
use Illuminate\Http\Request;

/**
 * @property-read ProfileRecordGroup $resource
 */
class ProfileRecordGroupResource extends BaseJsonResource
{
    public const array LOAD = ['translations'];

    /**
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->only([
                'id', 'key', 'sort_order',
            ]),
            'name' => $this->resource->name ?: $this->resource->key,
        ];
    }
}
