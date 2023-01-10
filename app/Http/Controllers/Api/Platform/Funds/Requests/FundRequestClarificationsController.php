<?php

namespace App\Http\Controllers\Api\Platform\Funds\Requests;

use App\Http\Requests\Api\Platform\Funds\Requests\Clarifications\IndexFundRequestClarificationsRequest;
use App\Http\Requests\Api\Platform\Funds\Requests\Clarifications\UpdateFundRequestClarificationsRequest;
use App\Http\Resources\FundRequestClarificationResource;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Http\Controllers\Controller;
use App\Services\FileService\Models\File;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundRequestClarificationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundRequestClarificationsRequest $request
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundRequestClarificationsRequest $request,
        Fund $fund,
        FundRequest $fundRequest
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyRequester', [
            FundRequestClarification::class, $fundRequest, $fund
        ]);

        return FundRequestClarificationResource::queryCollection(
            $fundRequest->clarifications(),
            $request
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @param FundRequestClarification $requestClarification
     * @return FundRequestClarificationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Fund $fund,
        FundRequest $fundRequest,
        FundRequestClarification $requestClarification
    ): FundRequestClarificationResource {
        $this->authorize('viewRequester', [$requestClarification, $fundRequest, $fund]);

        return FundRequestClarificationResource::create($requestClarification);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundRequestClarificationsRequest $request
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @param FundRequestClarification $requestClarification
     * @return FundRequestClarificationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundRequestClarificationsRequest $request,
        Fund $fund,
        FundRequest $fundRequest,
        FundRequestClarification $requestClarification
    ): FundRequestClarificationResource {
        $this->authorize('update', [$requestClarification, $fundRequest, $fund]);

        $requestClarification->update(array_merge($request->only('answer'), [
            'answered_at' => now(),
            'state' => FundRequestClarification::STATE_ANSWERED,
        ]));

        $requestClarification->appendFilesByUid($request->input('files', []));

        return FundRequestClarificationResource::create($requestClarification);
    }
}
