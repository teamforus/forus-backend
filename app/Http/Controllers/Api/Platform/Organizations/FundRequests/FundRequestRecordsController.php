<?php

namespace App\Http\Controllers\Api\Platform\Organizations\FundRequests;

use App\Events\FundRequestRecords\FundRequestRecordDeclined;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\ApproveFundRequestRecordRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\DeclineFundRequestRecordRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\IndexFundRequestRecordsRequest;
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

        return ValidatorFundRequestRecordResource::collection(
            $fundRequest->records()->paginate(
                $request->input('per_page')
            )
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $fundRequestClarification
     * @return ValidatorFundRequestRecordResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        FundRequest $fundRequest,
        FundRequestRecord $fundRequestClarification
    ): ValidatorFundRequestRecordResource {
        $this->authorize('viewAsValidator', [
            $fundRequestClarification, $fundRequest, $organization
        ]);

        return new ValidatorFundRequestRecordResource($fundRequestClarification);
    }

    /**
     * Approve fund request record.
     *
     * @param ApproveFundRequestRecordRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $fundRequestRecord
     * @return ValidatorFundRequestRecordResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function approve(
        ApproveFundRequestRecordRequest $request,
        Organization $organization,
        FundRequest $fundRequest,
        FundRequestRecord $fundRequestRecord
    ): ValidatorFundRequestRecordResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);
        $this->authorize('resolveAsValidator', [
            $fundRequestRecord, $fundRequest, $organization
        ]);

        $fundRequestRecord->approve($request->input('note'));

        return new ValidatorFundRequestRecordResource($fundRequestRecord);
    }

    /**
     * Decline fund request record.
     *
     * @param DeclineFundRequestRecordRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $fundRequestRecord
     * @return ValidatorFundRequestRecordResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function decline(
        DeclineFundRequestRecordRequest $request,
        Organization $organization,
        FundRequest $fundRequest,
        FundRequestRecord $fundRequestRecord
    ): ValidatorFundRequestRecordResource {
        $this->authorize('resolveAsValidator', [$fundRequest, $organization]);
        $this->authorize('resolveAsValidator', [
            $fundRequestRecord, $fundRequest, $organization
        ]);

        FundRequestRecordDeclined::dispatch($fundRequestRecord->decline(
            $request->input('note')
        ));

        return new ValidatorFundRequestRecordResource($fundRequestRecord);
    }
}
