<?php

namespace App\Http\Resources;

use App\Models\FundRequestClarification;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundRequestClarificationResource
 * @property FundRequestClarification $resource
 * @package App\Http\Resources
 */
class FundRequestClarificationResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return array_merge(array_only($this->resource->toArray(), [
            'id', 'question', 'answer', 'fund_request_record_id', 'state',
            'answered_at', 'created_at', 'updated_at',
        ]), [
            'answered_at_locale' => format_datetime_locale(
                $this->resource->answered_at
            ),
            'created_at_locale' => format_datetime_locale(
                $this->resource->created_at
            ),
            'updated_at_locale' => format_datetime_locale(
                $this->resource->updated_at
            ),
            'files' => FileResource::collection($this->resource->files),
        ]);
    }
}
