<?php

namespace App\Http\Resources\Validator;

use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\FileResource;
use App\Http\Resources\FundRequestClarificationResource;
use App\Models\FundRequestRecord;
use App\Models\Organization;
use Illuminate\Http\Request;

/**
 * @property FundRequestRecord $resource
 */
class ValidatorFundRequestRecordResource extends BaseJsonResource
{
    public const array LOAD = [
        'fund_request',
        'logs',
        'record_type.translations',
        'record_type.record_type_options.translations',
    ];

    public const array LOAD_NESTED = [
        'files' => FileResource::class,
        'fund_request_clarifications' => FundRequestClarificationResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        $baseRequest = BaseFormRequest::createFrom($request);
        $identityAddress = $baseRequest->auth_address() or abort(403);

        /** @var Organization $organization */
        $organization = $request->route('organization') or abort(403);
        $employee = $organization->findEmployee($identityAddress) or abort(403);

        return ValidatorFundRequestResource::recordToArray($this->resource, $employee);
    }
}
