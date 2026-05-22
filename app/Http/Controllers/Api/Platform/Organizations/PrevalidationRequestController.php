<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\PrevalidationRequests\PrevalidationRequestDeletedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\PrevalidationRequests\ApproveMissedRecordsRequest;
use App\Http\Requests\Api\Platform\Organizations\PrevalidationRequests\ResubmitFailedPrevalidationRequestsRequest;
use App\Http\Requests\Api\Platform\Organizations\PrevalidationRequests\SearchPrevalidationRequestsRequest;
use App\Http\Requests\Api\Platform\Organizations\PrevalidationRequests\StorePrevalidationRequestNoteRequest;
use App\Http\Requests\Api\Platform\Organizations\PrevalidationRequests\UploadPrevalidationRequestsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Identities\IdentitiesPersonRequest;
use App\Http\Requests\BaseIndexFormRequest;
use App\Http\Resources\Arr\IdentityPersonArrResource;
use App\Http\Resources\NoteResource;
use App\Http\Resources\PrevalidationRequestResource;
use App\Http\Responses\NoContentResponse;
use App\Models\Note;
use App\Models\Organization;
use App\Models\PrevalidationRequest;
use App\Scopes\Builders\PrevalidationRequestQuery;
use App\Searches\PrevalidationRequestSearch;
use App\Services\IConnectApiService\IConnect;
use App\Services\IConnectApiService\IConnectPrefill;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

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
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @return PrevalidationRequestResource
     */
    public function show(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
    ): PrevalidationRequestResource {
        $this->authorize('view', [$prevalidationRequest, $organization]);

        return PrevalidationRequestResource::create($prevalidationRequest);
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

        Event::dispatch(new PrevalidationRequestDeletedEvent($prevalidationRequest, null));
        $prevalidationRequest->delete();

        return new NoContentResponse();
    }

    /**
     * Display the specified resource.
     *
     * @param BaseIndexFormRequest $request
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function notes(
        BaseIndexFormRequest $request,
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyNote', [$prevalidationRequest, $organization]);

        return NoteResource::queryCollection($prevalidationRequest->notes()->whereRelation('employee', [
            'organization_id' => $organization->id,
        ]), $request);
    }

    /**
     * Display the specified resource.
     *
     * @param StorePrevalidationRequestNoteRequest $request
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @throws AuthorizationException
     * @return NoteResource
     * @noinspection PhpUnused
     */
    public function storeNote(
        StorePrevalidationRequestNoteRequest $request,
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
    ): NoteResource {
        $this->authorize('storeNote', [$prevalidationRequest, $organization]);

        return NoteResource::create($prevalidationRequest->addNote(
            $request->input('description'),
            $request->employee($organization),
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @param Note $note
     * @throws AuthorizationException
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function destroyNote(
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
        Note $note,
    ): JsonResponse {
        $this->authorize('destroyNote', [$prevalidationRequest, $organization, $note]);

        $note->delete();

        return new JsonResponse();
    }

    /**
     * @param ApproveMissedRecordsRequest $request
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @return PrevalidationRequestResource
     */
    public function approveMissedRecords(
        ApproveMissedRecordsRequest $request,
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
    ): PrevalidationRequestResource {
        $this->authorize('approveMissedRecords', [$prevalidationRequest, $organization]);

        $prevalidationRequest->approveMissedRecordsAndMakePrevalidation(
            $request->employee($organization),
            $request->input('note')
        );

        return PrevalidationRequestResource::create($prevalidationRequest);
    }

    /**
     * @param IdentitiesPersonRequest $request
     * @param Organization $organization
     * @param PrevalidationRequest $prevalidationRequest
     * @return IdentityPersonArrResource
     */
    public function person(
        IdentitiesPersonRequest $request,
        Organization $organization,
        PrevalidationRequest $prevalidationRequest,
    ) {
        $this->authorize('viewPersonBSNData', [$prevalidationRequest, $organization]);

        $bsn = $prevalidationRequest->bsn;
        $bsnService = IConnect::make($organization->getIConnectApiConfigs());
        $person = $bsnService->getPerson($bsn, ['parents', 'children', 'partners']);

        $scope = $request->input('scope');
        $scope_id = $request->input('scope_id');

        if ($person && $person->response()->success() && $scope && $scope_id) {
            if (!$relation = $person->getRelatedByIndex($scope, $scope_id)) {
                abort(404, 'Relation not found.');
            }

            $person = $relation->getBSN() ? $bsnService->getPerson($relation->getBSN()) : $relation;
        }

        if (!$person || $person->response() && $person->response()->error()) {
            if ($person && $person->response()->getCode() === 404) {
                abort(404, 'iConnect error, person not found.');
            }

            $errorMessage = $person ? 'Person bsn service, unknown error.' : 'Person bsn service, connection error.';

            Log::channel('person_bsn_api')->debug($errorMessage);
            abort(400, $errorMessage);
        }

        return IdentityPersonArrResource::create($person);
    }
}
