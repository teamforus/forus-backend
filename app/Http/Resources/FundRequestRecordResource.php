<?php

namespace App\Http\Resources;

use App\Models\FundRequestRecord;
use App\Scopes\Builders\FundRequestRecordQuery;
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

        $isValidValidator = FundRequestRecordQuery::whereIdentityCanBeValidatorFilter(
            FundRequestRecord::whereId($this->resource->id),
            auth_address()
        )->exists();

        $isAssignedValidator = FundRequestRecordQuery::whereIdentityIsAssignedEmployeeFilter(
            FundRequestRecord::whereId( $this->resource->id),
            auth_address()
        )->exists();

        return array_merge(array_only($this->resource->toArray(), array_merge([
            'id', 'state', 'record_type_key', 'fund_request_id',
            'created_at', 'updated_at', 'employee_id',
        ], $isValidValidator ? [
            'value'
        ] : [])), array_merge($isAssignedValidator ? [
            'files' => FileResource::collection($this->resource->files),
            'clarifications' => FundRequestClarificationResource::collection(
                $this->resource->fund_request_clarifications
            ),
        ] : [
            'files' => [],
            'clarifications' => [],
        ], [
            'record_type' => $recordTypes[$this->resource->record_type_key],
            'created_at_locale' => format_datetime_locale($this->resource->created_at),
            'updated_at_locale' => format_datetime_locale($this->resource->updated_at),
            'available' => $isValidValidator
        ]));
    }
}
