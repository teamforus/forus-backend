<?php

namespace App\Http\Resources;

use App\Models\FundRequest;

/**
 * Class FundRequestResource
 * @property FundRequest $resource
 * @package App\Http\Resources
 */
class FundRequestResource extends BaseJsonResource
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
            'id', 'state', 'employee_id', 'fund_id',
        ]), [
            'fund' => new FundResource($this->resource->fund),
            'records' => FundRequestRecordResource::collection($this->resource->records),
        ], $this->timestamps($this->resource, 'created_at', 'updated_at'));
    }
}
