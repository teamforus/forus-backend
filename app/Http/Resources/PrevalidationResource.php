<?php

namespace App\Http\Resources;

use App\Models\Prevalidation;

/**
 * @property Prevalidation $resource
 */
class PrevalidationResource extends BaseJsonResource
{
    public const LOAD = [
        'prevalidation_records.record_type.translations'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $records = $this->resource->prevalidation_records->sortByDesc('record_type_id');

        return [
            ...$this->resource->only([
                'id', 'uid', 'records_hash', 'uid_hash', 'state', 'exported', 'fund_id',
                'identity_address',
            ]),
            'records' => PrevalidationRecordResource::collection($records),
            ...$this->makeTimestamps($this->resource->only(['created_at'])),
        ];
    }
}
