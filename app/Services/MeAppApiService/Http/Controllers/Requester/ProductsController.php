<?php

namespace App\Services\MeAppApiService\Http\Controllers\Requester;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Models\Product;
use App\Searches\ProductSearch;
use App\Services\MeAppApiService\Http\Requests\Requester\IndexProductsRequest;
use App\Services\MeAppApiService\Http\Resources\Requester\ProductResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ProductsController extends Controller
{
    /**
     * List products.
     *
     * @param IndexProductsRequest $request
     * @throws AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(IndexProductsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAnyPublic', Product::class);

        $search = new ProductSearch(array_filter($request->only([
            'product_category_ids', 'fund_ids', 'organization_id', 'q', 'order_by', 'order_dir', 'postcode', 'distance',
            'qr', 'reservation', 'extra_payment', 'regular', 'discount_fixed', 'discount_percentage',
            'free', 'informational',
        ]), static fn ($value) => $value !== null), Product::query());

        $query = $search->queryWebshopSearch();

        if ($request->input('bookmarked', false)) {
            $query->whereRelation('bookmarks', 'identity_address', $request->auth_address());
        }

        $query->where('show_on_webshop', true);

        $priceField = 'price_min';
        $priceQuery = (clone $query);

        $priceMax = DB::table(DB::raw("({$priceQuery->toSql()}) as sub"))
            ->mergeBindings($priceQuery->getQuery())
            ->max($priceField);

        if (($from = $request->input('from')) !== null) {
            $query = $query->having($priceField, '>=', intval($from));
        }

        if (($to = $request->input('to')) !== null) {
            $query = $query->having($priceField, '<=', intval($to));
        }

        return ProductResource::queryCollection($query, $request)
            ->withQuery(array_filter($request->validated(), static fn ($value) => $value !== null))
            ->additional([
                'meta' => ['price_max' => intval($priceMax ?: 0)],
            ]);

    }

    /**
     * Get product details.
     *
     * @param Product $product
     * @throws AuthorizationException
     * @return ProductResource
     */
    public function show(Product $product): ProductResource
    {
        $this->authorize('showPublic', $product);

        return ProductResource::create($product);
    }

    /**
     * Bookmark a product.
     *
     * @param BaseFormRequest $request
     * @param Product $product
     * @throws AuthorizationException
     * @return ProductResource
     */
    public function bookmark(BaseFormRequest $request, Product $product): ProductResource
    {
        $this->authorize('bookmark', $product);

        return ProductResource::create($product->addBookmark($request->identity()));
    }

    /**
     * Remove a product bookmark.
     *
     * @param BaseFormRequest $request
     * @param Product $product
     * @throws AuthorizationException
     * @return ProductResource
     */
    public function removeBookmark(BaseFormRequest $request, Product $product): ProductResource
    {
        $this->authorize('removeBookmark', $product);

        return ProductResource::create($product->removeBookmark($request->identity()));
    }
}
