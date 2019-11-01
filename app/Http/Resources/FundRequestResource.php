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
    public function toArray($request)
    {
        $fundRequest = $this->resource;

        $isValidator = \Gate::allows('viewValidator', [
            FundRequest::class, $fundRequest, $fundRequest->fund
        ]);

        return array_merge(array_only($fundRequest->toArray(), $isValidator ? [
            'id', 'state', 'employee_id', 'fund_id', 'created_at', 'updated_at'
        ] : [
            'id', 'state', 'fund_id', 'created_at', 'updated_at'
        ]), [
            'created_at_locale' => format_datetime_locale(
                $this->resource->created_at
            ),
            'updated_at_locale' => format_datetime_locale(
                $this->resource->updated_at
            ),
        ], array_merge($isValidator ? [
            'employee' => new EmployeeResource($fundRequest->employee)
        ] : [], [
            'records' => FundRequestRecordResource::collection(
                $fundRequest->records
            )
        ]));
    }
}
