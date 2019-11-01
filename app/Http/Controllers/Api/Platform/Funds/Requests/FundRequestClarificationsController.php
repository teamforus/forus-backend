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
    ) {

        $this->authorize('indexRequester', [
            FundRequestClarification::class, $fundRequest, $fund
        ]);

        return FundRequestClarificationResource::collection(
            $fundRequest->clarifications()->paginate(
                $request->input('per_page')
            )
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @param FundRequestClarification $fundRequestClarification
     * @return FundRequestClarificationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Fund $fund,
        FundRequest $fundRequest,
        FundRequestClarification $fundRequestClarification
    ) {
        $this->authorize('viewRequester', [
            $fundRequestClarification, $fundRequest, $fund
        ]);

        return new FundRequestClarificationResource($fundRequestClarification);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundRequestClarificationsRequest $request
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @param FundRequestClarification $fundRequestClarification
     * @return FundRequestClarificationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateFundRequestClarificationsRequest $request,
        Fund $fund,
        FundRequest $fundRequest,
        FundRequestClarification $fundRequestClarification
    ) {
        $this->authorize('update', [
            $fundRequestClarification, $fundRequest, $fund
        ]);

        $fundRequestClarification->update(array_merge($request->only([
            'answer'
        ]), [
            'answered_at' => now(),
            'state' => FundRequestClarification::STATE_ANSWERED,
        ]));

        foreach ($request->input('files', []) as $fileUid) {
            $fundRequestClarification->attachFile(File::findByUid($fileUid));
        }

        return new FundRequestClarificationResource($fundRequestClarification);
    }
}
