<?php

namespace App\Http\Controllers\Api\Platform;

use App\Exports\PrevalidationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Prevalidations\SearchPrevalidationsRequest;
use App\Http\Requests\Api\Platform\Prevalidations\StorePrevalidationsRequest;
use App\Http\Requests\Api\Platform\Prevalidations\UploadPrevalidationsRequest;
use App\Http\Resources\Arr\ExportFieldArrResource;
use App\Http\Resources\PrevalidationResource;
use App\Models\Fund;
use App\Models\Prevalidation;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PrevalidationController extends Controller
{
    /**
     * @param StorePrevalidationsRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return PrevalidationResource
     * @noinspection PhpUnused
     */
    public function store(StorePrevalidationsRequest $request): PrevalidationResource
    {
        $this->authorize('store', Prevalidation::class);

        return PrevalidationResource::create(Prevalidation::storePrevalidations(
            $request->identity(),
            Fund::find($request->input('fund_id')),
            [$request->input('data')]
        )->first());
    }

    /**
     * @param UploadPrevalidationsRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function storeCollection(
        UploadPrevalidationsRequest $request,
    ): AnonymousResourceCollection {
        $this->authorize('store', Prevalidation::class);

        $file = $request->post('file');
        $fund = Fund::find($request->input('fund_id'));
        $data = $request->input('data', []);

        $employee = $fund?->organization?->findEmployee($request->auth_address());
        $event = $employee?->logCsvUpload($employee::EVENT_UPLOADED_PREVALIDATIONS, $file, $data);

        $prevalidations = Prevalidation::storePrevalidations(
            $request->identity(),
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return array
     * @noinspection PhpUnused
     */
    public function collectionHash(UploadPrevalidationsRequest $request): array
    {
        $this->authorize('store', Prevalidation::class);

        $fund = Fund::find($request->input('fund_id'));
        $primaryKey = $fund->fund_config->csv_primary_key;

        return [
            'db' => Prevalidation::where([
                'fund_id' => $fund->id,
                'identity_address' => $request->auth_address(),
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
     * @param SearchPrevalidationsRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function index(
        SearchPrevalidationsRequest $request
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', Prevalidation::class);

        return PrevalidationResource::queryCollection(Prevalidation::search($request), $request);
    }

    /**
     * @return AnonymousResourceCollection
     * @noinspection PhpUnused
     */
    public function getExportFields(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Prevalidation::class);

        return ExportFieldArrResource::collection(PrevalidationsExport::getExportFields());
    }

    /**
     * @param SearchPrevalidationsRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @noinspection PhpUnused
     */
    public function export(
        SearchPrevalidationsRequest $request,
    ): BinaryFileResponse {
        $this->authorize('viewAny', Prevalidation::class);

        $type = $request->input('export_type', 'xls');
        $fileName = date('Y-m-d H:i:s') . '.' . $type;
        $fields = $request->input('fields', PrevalidationsExport::getExportFieldsRaw());
        $fileData = new PrevalidationsExport($request, $fields);

        return resolve('excel')->download($fileData, $fileName);
    }

    /**
     * Delete prevalidation.
     * @param Prevalidation $prevalidation
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws Exception
     * @return \Illuminate\Http\JsonResponse
     * @noinspection PhpUnused
     */
    public function destroy(
        Prevalidation $prevalidation
    ): JsonResponse {
        $this->authorize('destroy', $prevalidation);

        $prevalidation->delete();

        return new JsonResponse([]);
    }
}
