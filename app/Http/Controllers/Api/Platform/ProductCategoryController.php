<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\SearchProductCategoriesRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ProductCategoryController
 * @package App\Http\Controllers\Api\Platform
 */
class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param SearchProductCategoriesRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(SearchProductCategoriesRequest $request): AnonymousResourceCollection
    {
        return ProductCategoryResource::queryCollection(
            ProductCategory::search($request)->orderBy('id'),
            $request
        );
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
