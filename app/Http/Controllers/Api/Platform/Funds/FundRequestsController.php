<?php

namespace App\Http\Controllers\Api\Platform\Funds;

use App\Events\FundRequests\FundRequestCreated;
use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\StoreFundRequestRequest;
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
     * @param StoreFundRequestRequest $request
     * @param Fund $fund
     * @return FundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(StoreFundRequestRequest $request, Fund $fund)
    {
        $this->authorize('create', [
            FundRequest::class, $fund
        ]);

        $fundRequest = $fund->makeFundRequest(
            auth_address(),
            $request->input('records')
        );

        FundRequestCreated::dispatch($fundRequest);

        return new FundRequestResource($fundRequest);
    }

    /**
     * @param StoreFundRequestRequest $request
     * @param Fund $fund
     */
    public function storeValidate(StoreFundRequestRequest $request, Fund $fund) {}

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
