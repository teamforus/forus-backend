<?php

namespace App\Http\Resources;

use App\Models\RecordType;

/**
 * @property-read RecordType $resource
 */
class RecordTypeResource extends BaseJsonResource
{
    public static $wrap = false;

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $recordType = $this->resource;

        return array_merge($recordType->only('key', 'type', 'system'), [
            'name' => $recordType->name ?: $recordType->key,
        ]);
    }
}
