<?php

namespace App\Http\Controllers\Api\Platform\Organizations\FundRequests;

use App\Events\FundRequestClarifications\FundRequestClarificationRequested;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Funds\Requests\Clarifications\IndexFundRequestClarificationsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\Clarifications\StoreFundRequestClarificationsRequest;
use App\Http\Resources\FundRequestClarificationResource;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Models\Organization;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundRequestClarificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestClarificationsRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexFundRequestClarificationsRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyValidator', [
            FundRequestClarification::class, $fundRequest, $organization,
        ]);

        $query = $fundRequest->clarifications();

        if ($recordId = $request->input('fund_request_record_id', false)) {
            $query->where('fund_request_record_id', $recordId);
        }

        return FundRequestClarificationResource::queryCollection($query, $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreFundRequestClarificationsRequest $request
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundRequestClarificationResource
     */
    public function store(
        StoreFundRequestClarificationsRequest $request,
        Organization $organization,
        FundRequest $fundRequest
    ): FundRequestClarificationResource {
        $this->authorize('create', [FundRequestClarification::class, $fundRequest, $organization]);

        $clarification = $fundRequest->clarifications()->create($request->only([
            'question', 'fund_request_record_id',
        ]));

        FundRequestClarificationRequested::dispatch($clarification);

        return FundRequestClarificationResource::create($clarification);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param FundRequest $fundRequest
     * @param FundRequestClarification $fundRequestClarification
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundRequestClarificationResource
     */
    public function show(
        Organization $organization,
        FundRequest $fundRequest,
        FundRequestClarification $fundRequestClarification
    ): FundRequestClarificationResource {
        $this->authorize('viewValidator', [
            $fundRequestClarification, $fundRequest, $organization,
        ]);

        return FundRequestClarificationResource::create($fundRequestClarification);
    }
}
