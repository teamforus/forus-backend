<?php

namespace App\Services\MeAppApiService\Http\Controllers\Provider\Vouchers;

use App\Http\Controllers\Api\Platform\Provider\Vouchers\ProductVouchersController as LegacyProductVouchersController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Vouchers\ProductsVouchers\IndexProductVouchersRequest;
use App\Http\Resources\Provider\App\ProviderAppVoucherResource as LegacyProviderAppVoucherResource;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Services\MeAppApiService\Http\Resources\Provider\ProviderVoucherResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductVouchersController extends Controller
{
    /**
     * List redeemable product vouchers.
     *
     * @param IndexProductVouchersRequest $request
     * @param VoucherToken $voucherToken
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     * @scramble-return AnonymousResourceCollection<
     *     LengthAwarePaginator<int, Voucher>, array{}, ProviderVoucherResource
     * >
     */
    public function index(IndexProductVouchersRequest $request, VoucherToken $voucherToken): AnonymousResourceCollection
    {
        $response = resolve(LegacyProductVouchersController::class)->index($request, $voucherToken);
        $paginator = $response->resource;

        $paginator->setCollection($response->collection->map(
            static fn (LegacyProviderAppVoucherResource $resource): Voucher => $resource->resource,
        ));

        return ProviderVoucherResource::collection($paginator);
    }
}
