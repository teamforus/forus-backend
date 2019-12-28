<?php

namespace App\Http\Controllers\Api\Platform\Funds\Requests;

use App\Http\Requests\Api\Platform\Funds\Requests\Records\IndexFundRequestRecordsRequest;
use App\Http\Resources\FundRequestRecordResource;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Http\Controllers\Controller;

class FundRequestRecordsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestRecordsRequest $request
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundRequestRecordsRequest $request,
        Fund $fund,
        FundRequest $fundRequest
    ) {
        $this->authorize('indexRequester', [
            FundRequestRecord::class, $fundRequest, $fund
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
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @param FundRequestRecord $fundRequestRecord
     * @return FundRequestRecordResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Fund $fund,
        FundRequest $fundRequest,
        FundRequestRecord $fundRequestRecord
    ) {
        $this->authorize('viewRequester', [
            $fundRequestRecord, $fundRequest, $fund
        ]);

        return new FundRequestRecordResource($fundRequestRecord);
    }
}
