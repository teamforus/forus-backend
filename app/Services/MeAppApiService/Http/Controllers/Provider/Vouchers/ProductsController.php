<?php

namespace App\Services\MeAppApiService\Http\Controllers\Provider\Vouchers;

use App\Http\Controllers\Api\Platform\Provider\Vouchers\ProductsController as LegacyProductsController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Provider\Vouchers\Products\IndexProductsRequest;
use App\Http\Resources\Provider\App\ProviderAppProductResource as LegacyProviderAppProductResource;
use App\Models\Product;
use App\Models\VoucherToken;
use App\Services\MeAppApiService\Http\Resources\Provider\ProviderProductResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductsController extends Controller
{
    /**
     * List products available for this voucher.
     *
     * @param IndexProductsRequest $request
     * @param VoucherToken $voucherToken
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(IndexProductsRequest $request, VoucherToken $voucherToken): AnonymousResourceCollection
    {
        $response = resolve(LegacyProductsController::class)->index($request, $voucherToken);
        $paginator = $response->resource;

        $paginator->setCollection($response->collection->map(
            static fn (LegacyProviderAppProductResource $resource): Product => $resource->resource,
        ));

        $collection = ProviderProductResource::collection($paginator);

        $collection->collection->each(fn (ProviderProductResource $resource) => $resource->setAttributes([
            'voucher' => $voucherToken->voucher,
        ]));

        return $collection;
    }
}
