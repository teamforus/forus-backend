<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\FundRequests\IndexFundRequestsRequest;
use App\Http\Resources\Requester\FundRequestResource;
use App\Models\FundRequest;
use App\Http\Controllers\Controller;
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

        $query = new FundRequestSearch($request->only('archived', 'order_by', 'fund_id'));

        return FundRequestResource::queryCollection($query->query()->where([
            'identity_address' => $request->auth_address()
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
