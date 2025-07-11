<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\SearchProductsRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\ProductBasicResource;
use App\Http\Resources\Requester\ProductResource;
use App\Models\Product;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param SearchProductsRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(SearchProductsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAnyPublic', Product::class);

        $query = Product::search($request->only([
            'product_category_id', 'fund_id', 'price_type', 'unlimited_stock',
            'organization_id', 'q', 'order_by', 'order_dir', 'postcode', 'distance',
            'qr', 'reservation', 'extra_payment',
        ]));

        if ($request->input('bookmarked', false)) {
            $query->whereRelation('bookmarks', 'identity_address', $request->auth_address());
        }

        if (!$request->input('show_all', false)) {
            $query->where('show_on_webshop', true);
        }

        if ($request->has('simplified') && $request->input('simplified')) {
            return ProductBasicResource::queryCollection($query, $request);
        }

        $priceField = 'price_min';
        $priceQuery = (clone $query);

        $priceMax = DB::table(DB::raw("({$priceQuery->toSql()}) as sub"))
            ->mergeBindings($priceQuery->getQuery())
            ->max($priceField);

        if ($from = $request->input('from')) {
            $query = $query->having($priceField, '>=', intval($from));
        }

        if ($to = $request->input('to')) {
            $query = $query->having($priceField, '<=', intval($to));
        }

        return ProductResource::queryCollection($query, $request)->additional([
            'meta' => [ 'price_max' => intval($priceMax ?: 0) ],
        ]);
    }

    /**
     * @param SearchProductsRequest $request
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function sample(SearchProductsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAnyPublic', Product::class);

        $per_page = $request->input('per_page', 6);

        $resultProducts = Product::implementationSample()
            ->whereHas('medias')
            ->where('show_on_webshop', true)
            ->take($per_page)->groupBy('organization_id')->distinct()
            ->with(ProductResource::load())->get();

        if ($resultProducts->count() < 6) {
            $resultProducts = $resultProducts->merge(
                Product::implementationSample()->whereKeyNot($resultProducts->pluck('id'))
                    ->take($per_page - $resultProducts->count())
                    ->where('show_on_webshop', true)
                    ->with(ProductResource::load())->get()
            );
        }

        return ProductResource::collection($resultProducts->load(ProductResource::load()));
    }

    /**
     * @param Product $product
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ProductResource
     */
    public function show(Product $product): ProductResource
    {
        $this->authorize('showPublic', $product);

        return ProductResource::create($product);
    }

    /**
     * @param BaseFormRequest $request
     * @param Product $product
     * @throws AuthorizationException
     * @return ProductResource
     */
    public function bookmark(BaseFormRequest $request, Product $product): ProductResource
    {
        $this->authorize('bookmark', $product);

        return ProductResource::create($product->closure(function (Product $product) use ($request) {
            $product->addBookmark($request->identity());
        }));
    }

    /**
     * @param BaseFormRequest $request
     * @param Product $product
     * @throws AuthorizationException
     * @return ProductResource
     */
    public function removeBookmark(BaseFormRequest $request, Product $product): ProductResource
    {
        $this->authorize('removeBookmark', $product);

        return ProductResource::create($product->closure(function (Product $product) use ($request) {
            $product->removeBookmark($request->identity());
        }));
    }
}
