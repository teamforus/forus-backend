<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds\Requests;

use App\Events\FundRequestRecords\FundRequestRecordDeclined;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\IndexFundRequestRecordsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\UpdateFundRequestRecordRequest;
use App\Http\Resources\FundRequestRecordResource;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Http\Controllers\Controller;
use App\Models\Organization;

class FundRequestRecordsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestRecordsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundRequestRecordsRequest $request,
        Organization $organization,
        Fund $fund,
        FundRequest $fundRequest
    ) {
        $this->authorize('indexValidator', [
            FundRequestRecord::class, $fundRequest, $fund, $organization
        ]);

        return FundRequestRecordResource::collection(
            $fundRequest->records()->paginate(
                $request->input('per_page')
            )
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $fundRequestClarification
     * @return FundRequestRecordResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundRequest $fundRequest,
        FundRequestRecord $fundRequestClarification
    ) {
        $this->authorize('viewValidator', [
            $fundRequestClarification, $fundRequest, $fund, $organization
        ]);

        return new FundRequestRecordResource($fundRequestClarification);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundRequestRecordRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $fundRequestRecord
     * @return FundRequestRecordResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function update(
        UpdateFundRequestRecordRequest $request,
        Organization $organization,
        Fund $fund,
        FundRequest $fundRequest,
        FundRequestRecord $fundRequestRecord
    ) {
        $this->authorize('update', [$fundRequest, $fund, $organization]);
        $this->authorize('update', [
            $fundRequestRecord, $fundRequest, $fund, $organization
        ]);

        if ($request->input('state') === FundRequestRecord::STATE_DECLINED) {
            $fundRequestRecord->decline($request->input('note'));
            FundRequestRecordDeclined::dispatch($fundRequestRecord);
        } elseif ($request->input('state') === FundRequestRecord::STATE_APPROVED) {
            $fundRequestRecord->approve();
        }

        return new FundRequestRecordResource($fundRequestRecord);
    }
}
