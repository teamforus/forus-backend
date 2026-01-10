<?php

namespace App\Http\Resources;

use App\Models\FundRequestClarification;
use Illuminate\Http\Request;

/**
 * @property FundRequestClarification $resource
 */
class FundRequestClarificationResource extends BaseJsonResource
{
    public const array LOAD = [
        'fund_request_record.record_type',
        'fund_request_record.record_type.translation',
    ];

    public const array LOAD_NESTED = [
        'files' => FileResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'answer' => $this->resource->answer,
            'state' => $this->resource->state,
            'files' => FileResource::collection($this->resource->files),
            'question' => $this->resource->question,
            'text_requirement' => $this->resource->text_requirement,
            'files_requirement' => $this->resource->files_requirement,
            'fund_request_record_id' => $this->resource->fund_request_record_id,
            'fund_request_record_name' => $this->resource->fund_request_record->record_type->name,
            ...$this->makeTimestamps($this->resource->only([
                'answered_at', 'created_at', 'updated_at',
            ])),
        ];
    }
}
