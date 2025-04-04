<?php

namespace App\Http\Controllers\Api\Platform\Funds\Requests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Funds\Requests\Records\IndexFundRequestRecordsRequest;
use App\Http\Resources\FundRequestRecordResource;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundRequestRecordsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestRecordsRequest $request
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexFundRequestRecordsRequest $request,
        Fund $fund,
        FundRequest $fundRequest
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyAsRequester', [$fundRequest, $fund]);
        $this->authorize('viewAnyAsRequester', [FundRequestRecord::class, $fundRequest, $fund]);

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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundRequestRecordResource
     */
    public function show(
        Fund $fund,
        FundRequest $fundRequest,
        FundRequestRecord $fundRequestRecord
    ): FundRequestRecordResource {
        $this->authorize('viewAsRequester', [$fundRequest, $fund]);
        $this->authorize('viewAsRequester', [$fundRequestRecord, $fundRequest, $fund]);

        return new FundRequestRecordResource($fundRequestRecord);
    }
}
