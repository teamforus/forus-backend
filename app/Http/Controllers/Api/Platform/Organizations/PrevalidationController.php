<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Exports\PrevalidationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Prevalidations\SearchPrevalidationsRequest;
use App\Http\Requests\Api\Platform\Organizations\Prevalidations\StorePrevalidationsRequest;
use App\Http\Requests\Api\Platform\Organizations\Prevalidations\UploadPrevalidationsRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\PrevalidationResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Scopes\Builders\PrevalidationQuery;
use App\Searches\PrevalidationSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PrevalidationController extends Controller
{
    /**
     * @param SearchPrevalidationsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(
        SearchPrevalidationsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', [Prevalidation::class, $organization]);

        $query = PrevalidationQuery::whereVisibleToIdentity(
            $organization->prevalidations(),
            $request->auth_address(),
        );

        $search = new PrevalidationSearch($request->only([
            'q', 'fund_id', 'from', 'to', 'state', 'exported',
        ]), $query);

        return PrevalidationResource::queryCollection($search->query(), $request);
    }

    /**
     * @param StorePrevalidationsRequest $request
     * @param Organization $organization
     * @return PrevalidationResource
     */
    public function store(
        StorePrevalidationsRequest $request,
        Organization $organization,
    ): PrevalidationResource {
        $this->authorize('create', [Prevalidation::class, $organization]);

        return PrevalidationResource::create(Prevalidation::storePrevalidations(
            $request->employee($organization),
            Fund::find($request->input('fund_id')),
            [$request->input('data')]
        )->first());
    }

    /**
     * @param UploadPrevalidationsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function storeCollection(
        UploadPrevalidationsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('create', [Prevalidation::class, $organization]);

        $file = $request->post('file');
        $fund = Fund::find($request->input('fund_id'));
        $data = $request->input('data', []);

        $employee = $request->employee($organization);
        $event = $employee?->logCsvUpload($employee::EVENT_UPLOADED_PREVALIDATIONS, $file, $data);

        $prevalidations = Prevalidation::storePrevalidations(
            $employee,
            $fund,
            $data,
            $request->input('overwrite', [])
        )->load(PrevalidationResource::LOAD);

        $event?->forceFill([
            'data->uploaded_file_meta->state' => 'success',
            'data->uploaded_file_meta->created_ids' => $prevalidations->pluck('id')->toArray(),
        ])?->update();

        return PrevalidationResource::collection($prevalidations);
    }

    /**
     * Generate pre-validations hashes for frontend.
     *
     * @param UploadPrevalidationsRequest $request
     * @param Organization $organization
     * @return array
     */
    public function collectionHash(
        UploadPrevalidationsRequest $request,
        Organization $organization,
    ): array {
        $this->authorize('create', [Prevalidation::class, $organization]);

        $fund = Fund::find($request->input('fund_id'));
        $primaryKey = $fund->fund_config->csv_primary_key;

        $query = PrevalidationQuery::whereVisibleToIdentity(
            $organization->prevalidations(),
            $request->auth_address(),
        );

        return [
            'db' => $query->where([
                'fund_id' => $fund->id,
                'employee_id' => $request->employee($organization)->id,
                'state' => Prevalidation::STATE_PENDING,
            ])->select(['id', 'uid_hash', 'records_hash'])->get()->toArray(),
            'collection' => array_map(static function ($row) use ($primaryKey) {
                ksort($row);

                return [
                    'data' => $row,
                    'uid_hash' => hash('sha256', $row[$primaryKey]),
                    'records_hash' => hash('sha256', json_encode($row)),
                ];
            }, $request->input('data', [])),
        ];
    }

    /**
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function getExportFields(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Prevalidation::class, $organization]);

        return ExportFieldArrResource::collection(PrevalidationsExport::getExportFields());
    }

    /**
     * @param SearchPrevalidationsRequest $request
     * @param Organization $organization
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @return BinaryFileResponse
     */
    public function export(
        SearchPrevalidationsRequest $request,
        Organization $organization,
    ): BinaryFileResponse {
        $this->authorize('viewAny', [Prevalidation::class, $organization]);

        $query = PrevalidationQuery::whereVisibleToIdentity(
            $organization->prevalidations(),
            $request->auth_address(),
        );

        $search = new PrevalidationSearch($request->only([
            'q', 'fund_id', 'from', 'to', 'state', 'exported',
        ]), $query);

        (clone $search->query())->update([
            'exported' => true,
        ]);

        $type = $request->input('data_format', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.' . $type;
        $fields = $request->input('fields', PrevalidationsExport::getExportFieldsRaw());

        $fileData = new PrevalidationsExport($search->query(), $fields);

        return resolve('excel')->download($fileData, $fileName);
    }

    /**
     * Delete prevalidation.
     * @param Organization $organization
     * @param Prevalidation $prevalidation
     * @return JsonResponse
     */
    public function destroy(
        Organization $organization,
        Prevalidation $prevalidation,
    ): JsonResponse {
        $this->authorize('destroy', [$prevalidation, $organization]);

        $prevalidation->delete();

        return new JsonResponse([]);
    }
}
