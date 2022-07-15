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
        $isAssigned = auth()->id() === $this->resource->identity_address;

        $fields = array_merge([
            'id', 'uid', 'records_hash', 'uid_hash', 'state', 'exported', 'fund_id',
        ], $isAssigned ? ['identity_address'] : []);

        $records = $this->resource->prevalidation_records->sortByDesc('record_type_id');

        return array_merge($this->resource->only($fields), [
            'records' => PrevalidationRecordResource::collection($records),
        ], $isAssigned ? $this->timestamps('created_at') : []);
    }
}
