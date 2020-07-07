<?php

namespace App\Http\Resources;

use App\Models\FundRequest;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundRequestResource
 * @property FundRequest $resource
 * @package App\Http\Resources
 */
class FundRequestResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $fundRequest = $this->resource;

        return array_merge(array_only($fundRequest->toArray(), [
            'id', 'state', 'employee_id', 'fund_id', 'created_at', 'updated_at'
        ]), [
            'fund' => new FundResource($fundRequest->fund),
            'records' => FundRequestRecordResource::collection($fundRequest->records),
            'created_at_locale' => format_datetime_locale($this->resource->created_at),
            'updated_at_locale' => format_datetime_locale($this->resource->updated_at),
        ]);
    }
}
