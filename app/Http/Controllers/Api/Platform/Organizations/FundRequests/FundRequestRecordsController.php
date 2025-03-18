<?php

namespace App\Http\Controllers\Api\Platform\Organizations\FundRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\IndexFundRequestRecordsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\StoreFundRequestRecordRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\UpdateFundRequestRecordRequest;
use App\Http\Resources\Validator\ValidatorFundRequestRecordResource;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundRequestRecordsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestRecordsRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexFundRequestRecordsRequest $request,
        Organization $organization,
        FundRequest $fundRequest,
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyAsValidator', [
            FundRequestRecord::class, $fundRequest, $organization,
        ]);

        return ValidatorFundRequestRecordResource::queryCollection($fundRequest->records(), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreFundRequestRecordRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ValidatorFundRequestRecordResource
     */
    public function store(
        StoreFundRequestRecordRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestRecordResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);
        $this->authorize('addPartnerBsnNumber', [$fundRequest, $organization]);

        return ValidatorFundRequestRecordResource::create($fundRequest->records()->create($request->only([
            'value', 'record_type_key',
        ])));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundRequestRecordRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $record
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ValidatorFundRequestRecordResource
     */
    public function update(
        UpdateFundRequestRecordRequest $request,
        Organization $organization,
        FundRequest $fundRequest,
        FundRequestRecord $record,
    ): ValidatorFundRequestRecordResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);
        $this->authorize('updateAsValidator', [$record, $fundRequest, $organization]);

        $record->updateAsValidator($request->input('value'), $request->employee($organization));

        return ValidatorFundRequestRecordResource::create($record);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $record
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ValidatorFundRequestRecordResource
     */
    public function show(
        Organization $organization,
        FundRequest $fundRequest,
        FundRequestRecord $record
    ): ValidatorFundRequestRecordResource {
        $this->authorize('viewAsValidator', [$record, $fundRequest, $organization]);

        return ValidatorFundRequestRecordResource::create($record);
    }
}
