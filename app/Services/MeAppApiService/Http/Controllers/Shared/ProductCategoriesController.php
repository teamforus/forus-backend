<?php

namespace App\Services\MeAppApiService\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Searches\ProductCategorySearch;
use App\Services\MeAppApiService\Http\Requests\Shared\IndexProductCategoriesRequest;
use App\Services\MeAppApiService\Http\Resources\Shared\ProductCategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Config;

class ProductCategoriesController extends Controller
{
    /**
     * List product categories.
     *
     * @param IndexProductCategoriesRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(IndexProductCategoriesRequest $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'parent_id', 'used',
        ]);

        $filters['parent_id'] ??= 'null';
        $filters['show_on_webshop'] = true;
        $disabledCategories = Config::get('forus.product_categories.disabled_top_categories', []);
        $search = new ProductCategorySearch($filters, ProductCategory::query());
        $query = $search->query()->whereNotIn('id', $disabledCategories)->orderBy('id');

        return ProductCategoryResource::queryCollection($query, $request)->preserveQuery();
    }
}
