<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\PrevalidationRequests\PrevalidationRequestDeleted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\PrevalidationRequests\ResubmitFailedPrevalidationRequestsRequest;
use App\Http\Requests\Api\Platform\Organizations\PrevalidationRequests\SearchPrevalidationRequestsRequest;
use App\Http\Requests\Api\Platform\Organizations\PrevalidationRequests\UploadPrevalidationRequestsRequest;
use App\Http\Resources\PrevalidationRequestResource;
use App\Http\Responses\NoContentResponse;
use App\Models\Organization;
use App\Models\PrevalidationRequest;
use App\Scopes\Builders\PrevalidationRequestQuery;
use App\Searches\PrevalidationRequestSearch;
use App\Services\IConnectApiService\IConnectPrefill;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PrevalidationRequestController extends Controller
{
    /**
     * @param SearchPrevalidationRequestsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(
        SearchPrevalidationRequestsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [PrevalidationRequest::class, $organization]);

        $query = PrevalidationRequestQuery::whereVisibleToIdentity(
            $organization->prevalidation_requests(),
            $request->auth_address(),
        );

        $search = new PrevalidationRequestSearch($request->only([
            'q', 'fund_id', 'from', 'to', 'state',
        ]), $query);

        return PrevalidationRequestResource::queryCollection($search->query(), $request)->additional([
            'meta' => [
                'failed_count' => $organization
                    ->prevalidation_requests()
                    ->where('state', PrevalidationRequest::STATE_FAIL)
                    ->whereRelation(
                        'latest_failed_log',
                        'data->prevalidation_request_fail_reason',
                        IConnectPrefill::PREFILL_ERROR_CONNECTION_ERROR,
                    )
                    ->count(),
            ],
        ]);
    }

    /**
     * @param UploadPrevalidationRequestsRequest $request
     * @param Organization $organization
     * @return NoContentResponse
     */
    public function storeCollection(
        UploadPrevalidationRequestsRequest $request,
        Organization $organization,
    ): NoContentResponse {
        $this->authorize('create', [PrevalidationRequest::class, $organization]);

        $data = $request->input('data', []);
        $employee = $request->employee($organization);
        $fund = $organization->funds()->find($request->input('fund_id'));

        PrevalidationRequest::makeFromArray($fund, $employee, $data);

        return new NoContentResponse();
    }

    /**
     * Validate prevalidation requests CSV upload data.
     *
     * @param UploadPrevalidationRequestsRequest $request
     * @param Organization $organization
     * @return NoContentResponse
     */
    public function storeCollectionValidate(
        UploadPrevalidationRequestsRequest $request,
        Organization $organization,
    ): NoContentResponse {
        $this->authorize('create', [PrevalidationRequest::class, $organization]);

        return new NoContentResponse();
    }

    /**
     * Resubmit prevalidation request.
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @return PrevalidationRequestResource
     */
    public function resubmit(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
    ): PrevalidationRequestResource {
        $this->authorize('resubmit', [$prevalidationRequest, $organization]);

        return PrevalidationRequestResource::create($prevalidationRequest->resubmit());
    }

    /**
     * Resubmit failed prevalidation requests.
     * @param ResubmitFailedPrevalidationRequestsRequest $request
     * @param Organization $organization
     * @return NoContentResponse
     */
    public function resubmitFailed(
        ResubmitFailedPrevalidationRequestsRequest $request,
        Organization $organization,
    ): NoContentResponse {
        $this->authorize('resubmitFailed', [PrevalidationRequest::class, $organization]);

        $reason = $request->input('reason', IConnectPrefill::PREFILL_ERROR_CONNECTION_ERROR);

        $organization
            ->prevalidation_requests()
            ->where('state', PrevalidationRequest::STATE_FAIL)
            ->whereRelation('latest_failed_log', 'data->prevalidation_request_fail_reason', $reason)
            ->get()
            ->each(fn (PrevalidationRequest $prevalidationRequest) => $prevalidationRequest->resubmit());

        return new NoContentResponse();
    }

    /**
     * Delete prevalidation request.
     *
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @return NoContentResponse
     */
    public function destroy(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
    ): NoContentResponse {
        $this->authorize('destroy', [$prevalidationRequest, $organization]);

        PrevalidationRequestDeleted::dispatch($prevalidationRequest);
        $prevalidationRequest->delete();

        return new NoContentResponse();
    }
}
