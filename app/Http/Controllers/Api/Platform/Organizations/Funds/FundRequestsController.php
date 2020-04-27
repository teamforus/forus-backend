<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Http\Requests\Api\Platform\Funds\Requests\ApproveFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\AssignFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\DeclineFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\IndexFundRequestsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\ResignFundRequestsRequest;
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
        $this->authorize('viewAnyValidator', [
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
     * Assign fund request to employee
     *
     * @param AssignFundRequestsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return FundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function assign(
        AssignFundRequestsRequest $request,
        Organization $organization,
        Fund $fund,
        FundRequest $fundRequest
    ) {
        $this->authorize('assignAsValidator', [
            $fundRequest, $fund, $organization
        ]);

        return new FundRequestResource($fundRequest->assignEmployee(
            Employee::find($request->input('employee_id'))
        ));
    }

    /**
     * Resign employee from fund request
     *
     * @param ResignFundRequestsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return FundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function resign(
        ResignFundRequestsRequest $request,
        Organization $organization,
        Fund $fund,
        FundRequest $fundRequest
    ) {
        $this->authorize('resignAsValidator', [
            $fundRequest, $fund, $organization
        ]);

        return new FundRequestResource($fundRequest->resignEmployee(
            Employee::find($request->input('employee_id'))
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ApproveFundRequestsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return FundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function approve(
        ApproveFundRequestsRequest $request,
        Organization $organization,
        Fund $fund,
        FundRequest $fundRequest
    ) {
        $this->authorize('approveAsValidator', [
            $fundRequest, $fund, $organization
        ]);

        return new FundRequestResource($fundRequest->approve(
            Employee::find($request->input('employee_id'))
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param DeclineFundRequestsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return FundRequestResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function decline(
        DeclineFundRequestsRequest $request,
        Organization $organization,
        Fund $fund,
        FundRequest $fundRequest
    ) {
        $this->authorize('declineAsValidator', [
            $fundRequest, $fund, $organization
        ]);

        return new FundRequestResource($fundRequest->decline(
            Employee::find($request->input('employee_id')),
            $request->input('note', '')
        ));
    }
}
