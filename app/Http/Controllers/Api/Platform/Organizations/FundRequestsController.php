<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\StoreFundRequestRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\UpdateFundRequestsRequest;
use App\Http\Resources\FundRequestResource;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;

class FundRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestsRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundRequestsRequest $request,
        Organization $organization
    ) {
        $this->authorize('indexValidator', [
            FundRequest::class, $organization->funds[0], $organization
        ]);

        return FundRequestResource::collection(
            FundRequest::search($request, $organization)->paginate(
                $request->input('per_page')
            )
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @return FundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        FundRequest $fundRequest
    ) {
        $this->authorize('viewRequester', [
            $fundRequest, $fundRequest->fund, $organization
        ]);

        return new FundRequestResource($fundRequest);
    }
}
