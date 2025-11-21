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
use Illuminate\Support\Facades\DB;
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
            employee: $request->employee($organization),
            fund: Fund::find($request->input('fund_id')),
            data: [$request->input('data')],
            topUps: [],
            overwriteKeys: [],
        )->load(PrevalidationResource::LOAD)->first());
    }

    /**
     * @param UploadPrevalidationsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     * @throws \Throwable
     */
    public function storeCollection(
        UploadPrevalidationsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('create', [Prevalidation::class, $organization]);

        DB::beginTransaction();

        $file = $request->post('file');
        $fund = Fund::find($request->input('fund_id'));
        $data = $request->input('data', []);
        $overwrite = $request->input('overwrite', []);
        $topUp = $request->input('top_up', []);

        $employee = $request->employee($organization);
        $event = $employee?->logCsvUpload($employee::EVENT_UPLOADED_PREVALIDATIONS, $file, $data);

        $prevalidations = Prevalidation::storePrevalidations(
            employee: $employee,
            fund: $fund,
            data: $data,
            topUps: $topUp,
            overwriteKeys: $overwrite,
        );

        $event?->forceFill([
            'data->uploaded_file_meta->state' => 'success',
            'data->uploaded_file_meta->top_up' => $topUp,
            'data->uploaded_file_meta->overwrite' => $overwrite,
            'data->uploaded_file_meta->created_ids' => $prevalidations->pluck('id')->toArray(),
        ])?->update();

        DB::commit();

        return PrevalidationResource::collection($prevalidations->load(PrevalidationResource::LOAD));
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
        $data = $request->input('data', []);

        $uids = array_pluck($data, $fund->fund_config->csv_primary_key);

        return [
            'db' => Prevalidation::getDbState($organization, $fund, $request->employee($organization), $uids),
            'collection' => Prevalidation::getCollectionState($fund, $data),
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

        $type = $request->input('data_format', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.' . $type;

        $fields = $request->input('fields', PrevalidationsExport::getExportFieldsRaw());
        $fileData = new PrevalidationsExport($fields, $search->query());

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
