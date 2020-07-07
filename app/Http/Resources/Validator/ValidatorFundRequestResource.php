<?php

namespace App\Http\Resources\Validator;

use App\Http\Resources\FundResource;
use App\Models\FundRequest;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundRequestResource
 * @property FundRequest $resource
 * @package App\Http\Resources
 */
class ValidatorFundRequestResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $recordRepo = resolve('forus.services.record');
        $fundRequest = $this->resource;

        return array_merge(array_only($fundRequest->toArray(), [
            'id', 'state', 'fund_id', 'created_at', 'updated_at'
        ]), [
            'fund' => new FundResource($fundRequest->fund),
            'bsn' => $recordRepo->bsnByAddress($fundRequest->identity_address),
            'created_at_locale' => format_datetime_locale(
                $this->resource->created_at
            ),
            'updated_at_locale' => format_datetime_locale(
                $this->resource->updated_at
            ),
            'records' => ValidatorFundRequestRecordResource::collection(
                $fundRequest->records
            ),
        ]);
    }
}
