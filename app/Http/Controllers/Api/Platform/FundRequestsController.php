<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\FundRequests\IndexFundRequestsRequest;
use App\Http\Resources\Requester\FundRequestResource;
use App\Models\FundRequest;
use App\Searches\FundRequestSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(IndexFundRequestsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAnyAsRequester', FundRequest::class);

        $search = new FundRequestSearch($request->only([
            'archived', 'order_by', 'order_dir', 'fund_id',
        ]));

        return FundRequestResource::queryCollection($search->query()->where([
            'identity_id' => $request->auth_id(),
        ]), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param FundRequest $fundRequest
     * @return FundRequestResource
     */
    public function show(FundRequest $fundRequest): FundRequestResource
    {
        $this->authorize('viewAsRequester', [$fundRequest, $fundRequest->fund]);

        return FundRequestResource::create($fundRequest);
    }
}
