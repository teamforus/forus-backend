<?php

namespace App\Services\MeAppApiService\Http\Controllers\Requester;

use App\Http\Controllers\Api\Platform\VouchersController as LegacyVouchersController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Vouchers\IndexVouchersRequest;
use App\Http\Resources\VoucherCollectionResource as LegacyVoucherCollectionResource;
use App\Models\Voucher;
use App\Services\MeAppApiService\Http\Requests\Requester\ShowVoucherRequest;
use App\Services\MeAppApiService\Http\Resources\Requester\VoucherResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VouchersController extends Controller
{
    /**
     * List your vouchers.
     *
     * @param IndexVouchersRequest $request
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(IndexVouchersRequest $request): AnonymousResourceCollection
    {
        $response = resolve(LegacyVouchersController::class)->index($request);
        $paginator = $response->resource;

        $paginator->setCollection($response->collection->map(
            static fn (LegacyVoucherCollectionResource $resource): Voucher => $resource->resource,
        ));

        return VoucherResource::collection($paginator);
    }

    /**
     * Get voucher details.
     *
     * @param ShowVoucherRequest $request
     * @param Voucher $voucher
     * @throws AuthorizationException
     * @return VoucherResource
     */
    public function show(ShowVoucherRequest $request, Voucher $voucher): VoucherResource
    {
        $response = resolve(LegacyVouchersController::class)->show($voucher);

        return new VoucherResource($response->resource);
    }

    /**
     * Email your voucher.
     *
     * @param Voucher $voucher
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function sendEmail(Voucher $voucher): JsonResponse
    {
        return resolve(LegacyVouchersController::class)->sendEmail($voucher);
    }
}
