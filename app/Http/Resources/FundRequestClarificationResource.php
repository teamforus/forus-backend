<?php

namespace App\Http\Resources;

use App\Models\FundRequestClarification;

/**
 * Class FundRequestClarificationResource
 * @property FundRequestClarification $resource
 * @package App\Http\Resources
 */
class FundRequestClarificationResource extends BaseJsonResource
{
    public const LOAD = [
        'files',
        'fund_request_record.record_type',
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return array_merge(array_only($this->resource->toArray(), [
            'id', 'question', 'answer', 'fund_request_record_id', 'state',
        ]), [
            'files' => FileResource::collection($this->resource->files),
            'fund_request_record_name' => $this->resource->fund_request_record->record_type->name,
        ], $this->timestamps($this->resource, 'answered_at', 'created_at', 'updated_at'));
    }
}
