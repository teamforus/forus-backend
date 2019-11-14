<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\SearchProductsRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Http\Controllers\Controller;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource
     *
     * @param SearchProductsRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(SearchProductsRequest $request)
    {
        $this->authorize('indexPublic', Product::class);

        return ProductResource::collection(Product::search($request)->with(
            ProductResource::$load
        )->paginate($request->input('per_page', 15)));
    }

    /**
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function sample()
    {
        $this->authorize('indexPublic', Product::class);

        $products = Product::searchQuery()->has('medias')->take(6);
        $products->groupBy('organization_id')->distinct()->inRandomOrder();
        $resultProducts = $products->with(ProductResource::$load)->get();

        if ($resultProducts->count() < 6) {
            $resultProducts = $resultProducts->merge(
                Product::searchQuery()->whereKeyNot(
                    $resultProducts->pluck('id')
                )->inRandomOrder()->take(
                    6 - $resultProducts->count()
                )->with(ProductResource::$load)->get()
            );
        }

        return ProductResource::collection($resultProducts);
    }

    /**
     * @param Product $product
     * @return ProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Product $product)
    {
        $this->authorize('showPublic', $product);

        return new ProductResource($product->load(ProductResource::$load));
    }
}
