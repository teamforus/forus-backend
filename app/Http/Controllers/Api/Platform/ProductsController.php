<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\ProductResource;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Product;
use App\Http\Controllers\Controller;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $organizationIds = FundProvider::query()->whereIn(
            'fund_id', Fund::configuredFunds()->pluck('id')
        )->where([
            'state' => 'approved'
        ])->pluck('organization_id');

        return ProductResource::collection(Product::query()->whereIn(
            'organization_id', $organizationIds
        )->get());
    }

    /**
     * Display the specified resource.
     *
     * @param Product $product
     * @return ProductResource
     */
    public function show(Product $product)
    {
        return new ProductResource($product);
    }
}
