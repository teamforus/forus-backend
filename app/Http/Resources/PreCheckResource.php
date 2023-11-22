<?php

namespace App\Http\Resources;

use App\Models\PreCheck;

/**
 * @property PreCheck $resource
 */
class PreCheckResource extends BaseJsonResource
{
    public const LOAD = [
        'implementation', 'pre_check_records'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $preCheck = $this->resource;

        return array_merge($preCheck->only([
            'id', 'order', 'title', 'description', 'default',
        ]), [
            'implementation' => new ImplementationPrivateResource($preCheck->implementation),
            'pre_check_records' => PreCheckRecordResource::collection($preCheck->pre_check_records),
        ]);
    }
}
