<?php

namespace App\Http\Controllers\Api\Platform\Funds;

use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\StoreFundRequestsRequest;
use App\Http\Resources\FundRequestResource;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Http\Controllers\Controller;

class FundRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestsRequest $request
     * @param Fund $fund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(IndexFundRequestsRequest $request, Fund $fund)
    {
        $this->authorize('indexRequester', [
            FundRequest::class, $fund
        ]);

        return FundRequestResource::collection($fund->fund_requests()->where([
            'identity_address' => auth_address()
        ])->paginate(
            $request->input('per_page')
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFundRequestsRequest $request
     * @param Fund $fund
     * @return FundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(StoreFundRequestsRequest $request, Fund $fund)
    {
        $this->authorize('create', [
            FundRequest::class, $fund
        ]);

        return new FundRequestResource($fund->makeFundRequest(
            auth_address(),
            $request->input('records')
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return FundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Fund $fund, FundRequest $fundRequest)
    {
        $this->authorize('viewRequester', [$fundRequest, $fund]);

        return new FundRequestResource($fundRequest);
    }
}
