<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Requests\Api\Platform\SearchProductCategoriesRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;

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
    public function index(
        SearchProductCategoriesRequest $request
    ) {
        $query = ProductCategory::search($request);

        return ProductCategoryResource::collection(
            $query->with(ProductCategoryResource::$load)->orderBy('id')->paginate(
                $request->get('per_page', 200)
            )
        );
    }

    /**
     * Display a listing of the resource.
     *
     * @param ProductCategory $productCategory
     * @return ProductCategoryResource
     */
    public function show(
        ProductCategory $productCategory
    ) {
        return new ProductCategoryResource($productCategory);
    }
}
