<?php

namespace App\Http\Controllers\Api\Platform\FundRequests;

use App\Events\FundRequestClarifications\FundRequestClarificationReceived;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\FundRequests\FundRequestClarifications\UpdateFundRequestClarificationRequest;
use App\Http\Resources\FundRequestClarificationResource;
use App\Models\FundRequest;
use App\Models\FundRequestClarification;
use Illuminate\Support\Facades\DB;
use Throwable;

class FundRequestClarificationsController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param UpdateFundRequestClarificationRequest $request
     * @param FundRequest $fundRequest
     * @param FundRequestClarification $requestClarification
     * @throws Throwable
     * @return FundRequestClarificationResource
     */
    public function update(
        UpdateFundRequestClarificationRequest $request,
        FundRequest $fundRequest,
        FundRequestClarification $requestClarification
    ): FundRequestClarificationResource {
        return DB::transaction(function () use ($request, $fundRequest, $requestClarification) {
            $requestClarification = FundRequestClarification::query()
                ->lockForUpdate()
                ->findOrFail($requestClarification->id);

            $this->authorize('update', [$requestClarification, $fundRequest]);

            $requestClarification->update([
                'answer' => $requestClarification->text_requirement !== 'no' ? $request->post('answer') : null,
                'resolved_at' => now(),
                'state' => FundRequestClarification::STATE_ANSWERED,
            ]);

            if ($requestClarification->files_requirement !== 'no') {
                $requestClarification->appendFilesByUid($request->input('files', []));
            }

            FundRequestClarificationReceived::dispatch($requestClarification);

            return FundRequestClarificationResource::create($requestClarification);
        });
    }
}
