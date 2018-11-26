<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\ProductResource;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Implementation;
use App\Models\Product;
use App\Http\Controllers\Controller;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('indexPublic', Product::class);

        $organizationIds = FundProvider::query()->whereIn(
            'fund_id', Implementation::activeFunds()->pluck('id')
        )->where('state', 'approved')->pluck('organization_id');

        return ProductResource::collection(Product::query()->whereIn(
            'organization_id', $organizationIds
        )->where('sold_out', false)->where(
            'expire_at', '>', date('Y-m-d')
        )->get());
    }

    /**
     * @param Product $product
     * @return ProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Product $product)
    {
        $this->authorize('showPublic', $product);

        return new ProductResource($product);
    }
}
