<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds\Requests;

use App\Events\FundRequestClarifications\FundRequestClarificationCreated;
use App\Http\Requests\Api\Platform\Funds\Requests\Clarifications\IndexFundRequestClarificationsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\Clarifications\StoreFundRequestClarificationsRequest;
use App\Http\Resources\FundRequestClarificationResource;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Http\Controllers\Controller;
use App\Models\Organization;

class FundRequestClarificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestClarificationsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundRequestClarificationsRequest $request,
        Organization $organization,
        Fund $fund,
        FundRequest $fundRequest
    ) {
        $this->authorize('indexValidator', [
            FundRequestClarification::class, $fundRequest, $fund, $organization
        ]);

        $query = $fundRequest->clarifications();

        if ($recordId = $request->input('fund_request_record_id', false)) {
            $query->where('fund_request_record_id', $recordId);
        }

        return FundRequestClarificationResource::collection(
            $query->paginate(
                $request->input('per_page')
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFundRequestClarificationsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return FundRequestClarificationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreFundRequestClarificationsRequest $request,
        Organization $organization,
        Fund $fund,
        FundRequest $fundRequest
    ) {
        $this->authorize('create', [
            FundRequestClarification::class, $fundRequest, $fund, $organization
        ]);

        $clarification = $fundRequest->clarifications()->create($request->only([
            'question', 'fund_request_record_id'
        ]));

        FundRequestClarificationCreated::dispatch($clarification);

        return new FundRequestClarificationResource($clarification);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @param FundRequestClarification $fundRequestClarification
     * @return FundRequestClarificationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundRequest $fundRequest,
        FundRequestClarification $fundRequestClarification
    ) {
        $this->authorize('viewValidator', [
            $fundRequestClarification, $fundRequest, $fund, $organization
        ]);

        return new FundRequestClarificationResource($fundRequestClarification);
    }
}
