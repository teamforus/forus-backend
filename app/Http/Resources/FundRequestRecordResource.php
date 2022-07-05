<?php

namespace App\Http\Resources;

use App\Models\FundRequestRecord;

/**
 * Class FundRequestRecordResource
 * @property FundRequestRecord $resource
 * @package App\Http\Resources
 */
class FundRequestRecordResource extends BaseJsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $recordTypes = collect(record_types_cached())->keyBy('key');

        return array_merge($this->resource->only([
            'id', 'state', 'record_type_key', 'fund_request_id', 'employee_id', 'value',
        ]), [
            'record_type' => $recordTypes[$this->resource->record_type_key],
        ], $this->timestamps($this->resource, 'created_at', 'updated_at'));
    }
}
