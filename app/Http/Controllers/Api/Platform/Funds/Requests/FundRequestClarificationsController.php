<?php

namespace App\Http\Controllers\Api\Platform\Funds\Requests;

use App\Http\Requests\Api\Platform\Funds\Requests\Clarifications\UpdateFundRequestClarificationsRequest;
use App\Http\Resources\FundRequestClarificationResource;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use App\Http\Controllers\Controller;

class FundRequestClarificationsController extends Controller
{
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
