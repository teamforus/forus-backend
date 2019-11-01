<?php

namespace App\Http\Resources;

use App\Models\FundRequestRecord;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundRequestRecordResource
 * @property FundRequestRecord $resource
 * @package App\Http\Resources
 */
class FundRequestRecordResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $recordTypes = collect(record_types_cached())->keyBy('key');

        return array_merge(array_only($this->resource->toArray(), [
            'id', 'value', 'state', 'record_type_key', 'fund_request_id',
            'created_at', 'updated_at',
        ]), [
            'created_at_locale' => format_datetime_locale(
                $this->resource->created_at
            ),
            'updated_at_locale' => format_datetime_locale(
                $this->resource->updated_at
            ),
            'record_type' => $recordTypes[$this->resource->record_type_key],
            'files' => FileResource::collection($this->resource->files),
            'clarifications' => FundRequestClarificationResource::collection(
                $this->resource->fund_request_clarifications
            ),
        ]);
    }
}
