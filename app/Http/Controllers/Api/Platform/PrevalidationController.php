<?php

namespace App\Http\Controllers\Api\Platform;

use App\Exports\PrevalidationsExport;
use App\Http\Requests\Api\Platform\Prevalidations\RedeemPrevalidationRequest;
use App\Http\Requests\Api\Platform\Prevalidations\SearchPrevalidationsRequest;
use App\Http\Requests\Api\Platform\Prevalidations\StorePrevalidationsRequest;
use App\Http\Requests\Api\Platform\Prevalidations\UploadPrevalidationsRequest;
use App\Http\Resources\PrevalidationResource;
use App\Models\Fund;
use App\Models\Prevalidation;
use App\Traits\ThrottleWithMeta;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PrevalidationController extends Controller
{
    use ThrottleWithMeta;

    private $maxAttempts = 3;
    private $decayMinutes = 180;

    /**
     * RecordCategoryController constructor.
     */
    public function __construct() {
        $this->maxAttempts = env('ACTIVATION_CODE_ATTEMPTS', $this->maxAttempts);
        $this->decayMinutes = env('ACTIVATION_CODE_DECAY', $this->decayMinutes);
    }

    /**
     * @param StorePrevalidationsRequest $request
     * @return PrevalidationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
                'identity_address' => auth_address(),
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
     */
    public function export(
        SearchPrevalidationsRequest $request
    ): BinaryFileResponse {
        $this->authorize('viewAny', Prevalidation::class);

        return resolve('excel')->download(
            new PrevalidationsExport($request),
            date('Y-m-d H:i:s') . '.xls'
        );
    }

    /**
     * Redeem prevalidation.
     *
     * @param RedeemPrevalidationRequest $request
     * @param Prevalidation|null $prevalidation
     * @return PrevalidationResource
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function redeem(
        RedeemPrevalidationRequest $request,
        Prevalidation $prevalidation = null
    ): PrevalidationResource {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->responseWithThrottleMeta('to_many_attempts', $request);
        }

        $this->incrementLoginAttempts($request);

        if (!$prevalidation || !$prevalidation->exists()) {
            $this->responseWithThrottleMeta('not_found', $request, 'prevalidations', 404);
        }

        $this->authorize('redeem', $prevalidation);
        $this->clearLoginAttempts($request);

        $prevalidation->assignToIdentity(auth_address());

        return new PrevalidationResource($prevalidation);
    }
}
