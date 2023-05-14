<?php

namespace App\Http\Controllers\Api\Platform\Organizations\FundRequests;

use App\Http\Requests\Api\Platform\Funds\Requests\Records\ApproveFundRequestRecordRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\DeclineFundRequestRecordRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\IndexFundRequestRecordsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\StoreFundRequestRecordRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\UpdateFundRequestRecordRequest;
use App\Http\Resources\Validator\ValidatorFundRequestRecordResource;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Http\Controllers\Controller;
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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundRequestRecordsRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyValidator', [
            FundRequestRecord::class, $fundRequest, $organization
        ]);

        return ValidatorFundRequestRecordResource::queryCollection($fundRequest->records(), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param StoreFundRequestRecordRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return ValidatorFundRequestRecordResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreFundRequestRecordRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): ValidatorFundRequestRecordResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);
        $this->authorize('addPartnerBsnNumber', [$fundRequest, $organization]);

        /** @var FundRequestRecord $fundRequestRecord */
        $fundRequestRecord = $fundRequest->records()->create(array_merge($request->only([
            'value', 'record_type_key',
        ]), [
            'employee_id' => $request->employee($organization)->id,
        ]));

        $fundRequestRecord->approve();

        return ValidatorFundRequestRecordResource::create($fundRequestRecord);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundRequestRecordRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $record
     * @return ValidatorFundRequestRecordResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundRequestRecordRequest $request,
        Organization $organization,
        FundRequest $fundRequest,
        FundRequestRecord $record,
    ): ValidatorFundRequestRecordResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);

        $record->updateAsValidator($request->input('value'), $request->employee($organization));

        return ValidatorFundRequestRecordResource::create($record);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $record
     * @return ValidatorFundRequestRecordResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        FundRequest $fundRequest,
        FundRequestRecord $record
    ): ValidatorFundRequestRecordResource {
        $this->authorize('viewAsValidator', [$record, $fundRequest, $organization]);

        return ValidatorFundRequestRecordResource::create($record);
    }

    /**
     * Approve fund request record.
     *
     * @param ApproveFundRequestRecordRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $record
     * @return ValidatorFundRequestRecordResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function approve(
        ApproveFundRequestRecordRequest $request,
        Organization $organization,
        FundRequest $fundRequest,
        FundRequestRecord $record
    ): ValidatorFundRequestRecordResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);
        $this->authorize('resolveAsValidator', [$record, $fundRequest, $organization]);

        $record->approve($request->input('note'));

        return ValidatorFundRequestRecordResource::create($record);
    }

    /**
     * Decline fund request record.
     *
     * @param DeclineFundRequestRecordRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $record
     * @return ValidatorFundRequestRecordResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function decline(
        DeclineFundRequestRecordRequest $request,
        Organization $organization,
        FundRequest $fundRequest,
        FundRequestRecord $record
    ): ValidatorFundRequestRecordResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);
        $this->authorize('resolveAsValidator', [$record, $fundRequest, $organization]);

        $record->decline($request->input('note'));

        return ValidatorFundRequestRecordResource::create($record);
    }
}
