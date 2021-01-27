<?php

namespace App\Http\Resources\Validator;

use App\Models\FundRequestRecord;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\Resource;

/**
 * Class FundRequestRecordResource
 * @property FundRequestRecord $resource
 * @package App\Http\Resources
 */
class ValidatorFundRequestRecordResource extends Resource
{
    public static $load = [
        'employee', 'files', 'fund_request_clarifications'
    ];

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $identityAddress = auth_address(true);

        /** @var Organization $organization */
        $organization = $request->route('organization') or abort(403);
        $employee = $organization->findEmployee($identityAddress) or abort(403);

        return ValidatorFundRequestResource::recordToArray(
            $this->resource,
            $employee,
            $this->resource->isValueReadable($identityAddress, $employee->id)
        );
    }
}
