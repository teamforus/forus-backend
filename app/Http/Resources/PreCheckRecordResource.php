<?php

namespace App\Http\Resources;

use App\Models\PreCheckRecord;

/**
 * @property PreCheckRecord $resource
 */
class PreCheckRecordResource extends BaseJsonResource
{
    public const LOAD = [
        'record_type'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $preCheckRecord = $this->resource;

        return array_merge($preCheckRecord->only([
            'id', 'order', 'title', 'short_title', 'description', 'pre_check_id',
        ]), [
            'record_type' => new RecordTypeResource($preCheckRecord->record_type),
        ]);
    }
}
