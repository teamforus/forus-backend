<?php

namespace App\Http\Resources;

use App\Models\FundRequestRecord;

/**
 * @property FundRequestRecord $resource
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
        return array_merge($this->resource->only([
            'id', 'state', 'record_type_key', 'fund_request_id', 'employee_id', 'value',
            'fund_criterion_id',
        ]), [
            'record_type' => [
                ...$this->resource->record_type->only(['name', 'key', 'type']),
                'options' => $this->resource->record_type->getOptions(),
            ],
        ], $this->timestamps($this->resource, 'created_at', 'updated_at'));
    }
}
