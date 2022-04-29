<?php

namespace App\Http\Resources\Validator;

use App\Http\Resources\BaseJsonResource;
use App\Models\FundRequestRecord;
use App\Models\Organization;
use App\Scopes\Builders\EmployeeQuery;

/**
 * Class FundRequestRecordResource
 * @property FundRequestRecord $resource
 * @package App\Http\Resources
 */
class ValidatorFundRequestRecordResource extends BaseJsonResource
{
    public const LOAD = [
        'employee', 'files', 'fund_request_clarifications',
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

        $isAssignable = EmployeeQuery::whereCanValidateRecords($organization->employees(), [
            $this->resource->id
        ])->exists();

        return ValidatorFundRequestResource::recordToArray($this->resource, $employee, $isAssignable);
    }
}
