<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

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
     * @param Fund $fund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundRequestsRequest $request,
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('indexValidator', [
            FundRequest::class, $fund, $organization
        ]);

        return FundRequestResource::collection($fund->fund_requests()->paginate(
            $request->input('per_page')
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return FundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundRequest $fundRequest
    ) {
        $this->authorize('viewValidator', [
            $fundRequest, $fund, $organization
        ]);

        return new FundRequestResource($fundRequest);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundRequestsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return FundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function update(
        UpdateFundRequestsRequest $request,
        Organization $organization,
        Fund $fund,
        FundRequest $fundRequest
    ) {
        $this->authorize('update', [
            $fundRequest, $fund, $organization
        ]);

        // resign employee if employee_id is present and is null
        if (($employeeId = $request->input('employee_id', false)) !== false) {
            log_debug($employeeId);

            !$employeeId ? $fundRequest->resignEmployee() :
                $fundRequest->assignEmployee(Employee::find($employeeId));
        }

        // only fund requests with employee might be resolved
        if ($fundRequest->employee_id && $request->has('state')) {
            $fundRequest->resolve($request->input('state'));
        }

        return new FundRequestResource($fundRequest);
    }
}
