<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\SearchProductsRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\Requester\ProductResource;
use App\Models\FavouriteProduct;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ProductsController
 * @package App\Http\Controllers\Api\Platform
 */
class ProductsController extends Controller
{
    /**
     * Display a listing of the resource
     *
     * @param SearchProductsRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(SearchProductsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAnyPublic', Product::class);

        $query = Product::search($request->only([
            'fund_type', 'product_category_id', 'fund_id', 'price_type', 'unlimited_stock',
            'organization_id', 'q', 'order_by', 'order_by_dir', 'postcode',
            'distance', 'favourites_only'
        ]));

        if (!$request->input('show_all', false)) {
            $query->where('show_on_webshop', true);
        }

        return ProductResource::queryCollection($query, $request);
    }

    /**
     * @param SearchProductsRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function sample(SearchProductsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAnyPublic', Product::class);

        $per_page = $request->input('per_page', 6);

        $resultProducts = Product::searchSample($request)
            ->whereHas('medias')
            ->where('show_on_webshop', true)
            ->take($per_page)->groupBy('organization_id')->distinct()
            ->with(ProductResource::load())->get();

        if ($resultProducts->count() < 6) {
            $resultProducts = $resultProducts->merge(
                Product::searchSample($request)->whereKeyNot($resultProducts->pluck('id'))
                    ->take($per_page - $resultProducts->count())
                    ->where('show_on_webshop', true)
                    ->with(ProductResource::load())->get()
            );
        }

        return ProductResource::collection($resultProducts->load(ProductResource::load()));
    }

    /**
     * @param Product $product
     * @return ProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Product $product): ProductResource
    {
        $this->authorize('showPublic', $product);

        return ProductResource::create($product);
    }
}
