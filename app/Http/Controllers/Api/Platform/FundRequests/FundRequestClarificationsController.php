<?php

namespace App\Http\Controllers\Api\Platform\FundRequests;

use App\Events\FundRequestClarifications\FundRequestClarificationReceived;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\FundRequests\FundRequestClarifications\UpdateFundRequestClarificationRequest;
use App\Http\Resources\FundRequestClarificationResource;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;

class FundRequestClarificationsController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundRequestClarificationRequest $request
     * @param FundRequest $fundRequest
     * @param FundRequestClarification $requestClarification
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return FundRequestClarificationResource
     */
    public function update(
        UpdateFundRequestClarificationRequest $request,
        FundRequest $fundRequest,
        FundRequestClarification $requestClarification
    ): FundRequestClarificationResource {
        $this->authorize('update', [$requestClarification, $fundRequest]);

        $requestClarification->update(array_merge($request->only('answer'), [
            'answered_at' => now(),
            'state' => FundRequestClarification::STATE_ANSWERED,
        ]));

        $requestClarification->appendFilesByUid($request->input('files', []));

        FundRequestClarificationReceived::dispatch($requestClarification);

        return FundRequestClarificationResource::create($requestClarification);
    }
}
