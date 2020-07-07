<?php

namespace App\Http\Resources\Validator;

use App\Http\Resources\EmployeeResource;
use App\Http\Resources\FileResource;
use App\Http\Resources\FundRequestClarificationResource;
use App\Models\FundRequestRecord;
use App\Scopes\Builders\FundRequestRecordQuery;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundRequestRecordResource
 * @property FundRequestRecord $resource
 * @package App\Http\Resources
 */
class ValidatorFundRequestRecordResource extends Resource
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

        $is_value_readable = FundRequestRecordQuery::whereIdentityCanBeValidatorFilter(
            FundRequestRecord::whereId($this->resource->id),
            auth_address()
        )->exists();

        $is_assignable = FundRequestRecordQuery::whereIdentityCanBeValidatorFilter(
            FundRequestRecord::whereId($this->resource->id)->whereDoesntHave('employee'),
            auth_address()
        )->exists();

        $is_assigned = FundRequestRecordQuery::whereIdentityIsAssignedEmployeeFilter(
            FundRequestRecord::whereId($this->resource->id),
            auth_address()
        )->exists();

        $is_visible = $is_assignable || $is_assigned || $is_value_readable;

        return array_merge(array_only($this->resource->toArray(), array_merge([
            'id', 'state', 'record_type_key', 'fund_request_id',
            'created_at', 'updated_at', 'employee_id',
        ], $is_visible ? [
            'value'
        ] : [])), array_merge($is_assigned ? [
            'files' => FileResource::collection($this->resource->files),
            'clarifications' => FundRequestClarificationResource::collection(
                $this->resource->fund_request_clarifications
            ),
        ] : [
            'files' => [],
            'clarifications' => [],
        ], [
            'employee' => new EmployeeResource($this->resource->employee),
            'record_type' => $recordTypes[$this->resource->record_type_key],
            'created_at_locale' => format_datetime_locale($this->resource->created_at),
            'updated_at_locale' => format_datetime_locale($this->resource->updated_at),
        ], compact('is_assignable', 'is_assigned', 'is_visible')));
    }
}
