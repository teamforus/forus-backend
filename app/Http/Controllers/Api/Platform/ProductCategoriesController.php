<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\IndexProductCategoriesRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Searches\ProductCategorySearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Config;

class ProductCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductCategoriesRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexProductCategoriesRequest $request): AnonymousResourceCollection
    {
        $disabledCategories = Config::get('forus.product_categories.disabled_top_categories', []);
        $search = new ProductCategorySearch($request->only('q', 'parent_id', 'used', 'used_type'));
        $query = $search->query()->whereNotIn('id', $disabledCategories)->orderBy('id');

        return ProductCategoryResource::queryCollection($query, $request);
    }

    /**
     * Display a listing of the resource.
     *
     * @param ProductCategory $productCategory
     * @return ProductCategoryResource
     */
    public function show(ProductCategory $productCategory): ProductCategoryResource
    {
        return ProductCategoryResource::create($productCategory);
    }
}
