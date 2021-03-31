<?php

namespace App\Http\Controllers\Api\Platform;

use App\Exports\PrevalidationsExport;
use App\Http\Requests\Api\Platform\Prevalidations\SearchPrevalidationsRequest;
use App\Http\Requests\Api\Platform\Prevalidations\StorePrevalidationsRequest;
use App\Http\Requests\Api\Platform\Prevalidations\UploadPrevalidationsRequest;
use App\Http\Resources\PrevalidationResource;
use App\Models\Fund;
use App\Models\Prevalidation;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class PrevalidationController
 * @package App\Http\Controllers\Api\Platform
 */
class PrevalidationController extends Controller
{
    /**
     * @param StorePrevalidationsRequest $request
     * @return PrevalidationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function store(
        StorePrevalidationsRequest $request
    ): PrevalidationResource {
        $this->authorize('store', Prevalidation::class);

        /** @var Prevalidation $prevalidation */
        $prevalidation = Prevalidation::storePrevalidations(
            Fund::find($request->input('fund_id')),
            [$request->input('data')]
        )->first();

        return new PrevalidationResource($prevalidation);
    }

    /**
     * @param UploadPrevalidationsRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function storeCollection(
        UploadPrevalidationsRequest $request
    ): AnonymousResourceCollection {
        $this->authorize('store', Prevalidation::class);

        $prevalidations = Prevalidation::storePrevalidations(
            Fund::find($request->input('fund_id')),
            $request->input('data', []),
            $request->input('overwrite', [])
        );

        return PrevalidationResource::collection($prevalidations->load(
            PrevalidationResource::$load
        ));
    }

    /**
     * Generate pre-validations hashes for frontend
     *
     * @param UploadPrevalidationsRequest $request
     * @return array
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function collectionHash(
        UploadPrevalidationsRequest $request
    ): array {
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
            }, $request->input('data', []))
        ];
    }

    /**
     * @param SearchPrevalidationsRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function index(
        SearchPrevalidationsRequest $request
    ): AnonymousResourceCollection {
        $this->authorize('viewAny', Prevalidation::class);

        return PrevalidationResource::collection(Prevalidation::search($request)->with(
            PrevalidationResource::$load
        )->paginate($request->input('per_page')));
    }

    /**
     * @param SearchPrevalidationsRequest $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer
     * @noinspection PhpUnused
     */
    public function export(
        SearchPrevalidationsRequest $request
    ): BinaryFileResponse {
        $this->authorize('viewAny', Prevalidation::class);

        $type = $request->input('export_format', 'xls');

        return resolve('excel')->download(
            new PrevalidationsExport($request),
            date('Y-m-d H:i:s') . '.'. $type
        );
    }

    /**
     * Delete prevalidation
     * @param Prevalidation $prevalidation
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     * @noinspection PhpUnused
     */
    public function destroy(
        Prevalidation $prevalidation
    ): JsonResponse {
        $this->authorize('destroy', $prevalidation);

        $prevalidation->delete();

        return response()->json([]);
    }
}
