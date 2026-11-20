<?php

namespace App\Modules\RequesterImport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Modules\RequesterImport\Exceptions\RequesterImportErrorCode;
use App\Modules\RequesterImport\Exceptions\RequesterImportException;
use App\Modules\RequesterImport\Http\Requests\RequesterImportRows\IndexRequesterImportRowsRequest;
use App\Modules\RequesterImport\Http\Requests\RequesterImportRows\StoreRequesterImportRowsRequest;
use App\Modules\RequesterImport\Http\Requests\RequesterImportRows\SyncRequesterImportRowsRequest;
use App\Modules\RequesterImport\Http\Requests\RequesterImportRows\UpdateRequesterImportRowRequest;
use App\Modules\RequesterImport\Http\Resources\RequesterImportRowResource;
use App\Modules\RequesterImport\Models\RequesterImportRow;
use App\Modules\RequesterImport\Searches\RequesterImportRowsSearch;
use App\Modules\RequesterImport\Services\RequesterImportLogService;
use App\Modules\RequesterImport\Services\RequesterImportProfileSyncService;
use App\Modules\RequesterImport\Services\RequesterImportRowsService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Throwable;

class RequesterImportRowsController extends Controller
{
    /**
     * @param RequesterImportLogService $logService
     * @param RequesterImportRowsService $rowsService
     * @param RequesterImportProfileSyncService $profileSyncService
     */
    public function __construct(
        protected RequesterImportLogService $logService,
        protected RequesterImportRowsService $rowsService,
        protected RequesterImportProfileSyncService $profileSyncService,
    ) {
    }

    /**
     * @param IndexRequesterImportRowsRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexRequesterImportRowsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [RequesterImportRow::class, $organization]);

        $search = new RequesterImportRowsSearch($request->safe()->only([
            'q',
        ]), RequesterImportRow::query()->where('organization_id', $organization->id));

        return RequesterImportRowResource::queryCollection($search->query(), $request);
    }

    /**
     * @param Organization $organization
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function configs(Organization $organization): JsonResponse
    {
        $this->authorize('create', [RequesterImportRow::class, $organization]);

        try {
            $configuration = $this->rowsService->getConfiguration($organization);
        } catch (RequesterImportException $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'errors' => $exception->errors(),
            ], 422);
        }

        return new JsonResponse([
            'data' => $configuration,
        ]);
    }

    /**
     * @param StoreRequesterImportRowsRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @throws ValidationException
     * @return JsonResponse
     */
    public function storeBatchValidate(StoreRequesterImportRowsRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('create', [RequesterImportRow::class, $organization]);

        return new JsonResponse([
            'data' => $this->rowsService->previewBatch($organization, $request->validated('rows')),
        ]);
    }

    /**
     * @param StoreRequesterImportRowsRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function storeBatch(StoreRequesterImportRowsRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('create', [RequesterImportRow::class, $organization]);

        return new JsonResponse([
            'data' => $this->rowsService->importBatch(
                $organization,
                $request->validated('rows'),
                $request->employee($organization),
            ),
        ]);
    }

    /**
     * @param SyncRequesterImportRowsRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function syncPreview(SyncRequesterImportRowsRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('sync', [RequesterImportRow::class, $organization]);

        $employee = $request->employee($organization);

        abort_unless($employee !== null, 403);

        return new JsonResponse([
            'data' => $this->profileSyncService->previewOrganization(
                $organization,
                $employee,
                $request->boolean('update_all'),
                $request->validated('row_ids'),
            ),
        ]);
    }

    /**
     * @param SyncRequesterImportRowsRequest $request
     * @param Organization $organization
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function sync(SyncRequesterImportRowsRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('sync', [RequesterImportRow::class, $organization]);

        $employee = $request->employee($organization);

        abort_unless($employee !== null, 403);

        return new JsonResponse([
            'data' => $this->profileSyncService->syncOrganization(
                $organization,
                $employee,
                $request->boolean('update_all'),
                $request->validated('row_ids'),
            ),
        ]);
    }

    /**
     * @param Organization $organization
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function syncIds(Organization $organization): JsonResponse
    {
        $this->authorize('sync', [RequesterImportRow::class, $organization]);

        return new JsonResponse(['data' => $this->profileSyncService->candidateIds($organization)]);
    }

    /**
     * @param UpdateRequesterImportRowRequest $request
     * @param Organization $organization
     * @param RequesterImportRow $requesterImportRow
     * @throws AuthorizationException
     * @throws Throwable
     * @return RequesterImportRowResource|JsonResponse
     */
    public function update(
        UpdateRequesterImportRowRequest $request,
        Organization $organization,
        RequesterImportRow $requesterImportRow,
    ): RequesterImportRowResource|JsonResponse {
        $this->authorize('update', [$requesterImportRow, $organization]);

        $employee = $request->employee($organization);

        try {
            $row = $this->rowsService->updateRow($requesterImportRow, $request->validated('records'), $employee);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(collect($exception->errors())
                ->mapWithKeys(fn (array $messages, string $key) => ['records.' . $key => $messages])
                ->all());
        } catch (Throwable $exception) {
            $diagnosticId = $this->logService->failure(
                RequesterImportLogService::OPERATION_UPDATE,
                $exception,
                [
                    'organization_id' => $organization->id,
                    'employee_id' => $employee?->id,
                    'row_id' => $requesterImportRow->id,
                ],
                RequesterImportErrorCode::UPDATE_FAILED,
            );

            $errors = [];

            if ($exception instanceof RequesterImportException) {
                foreach ($exception->errors() as $key => $messages) {
                    $errors['records.' . $key] = $messages;
                }
            }

            return new JsonResponse([
                'message' => match (true) {
                    $exception instanceof RequesterImportException => $exception->getMessage(),
                    $exception instanceof ModelNotFoundException => trans('requester_import::validation.row_unavailable'),
                    default => trans('requester_import::validation.update_failed'),
                },
                'errors' => $errors,
                'diagnostic_id' => $diagnosticId,
            ], match (true) {
                $exception instanceof RequesterImportException => 422,
                $exception instanceof ModelNotFoundException => 404,
                default => 500,
            });
        }

        return RequesterImportRowResource::create($row);
    }
}
