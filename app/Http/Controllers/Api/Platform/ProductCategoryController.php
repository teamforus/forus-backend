<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\ProductCategoryResource;
use App\Models\Implementation;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

/**
 * Class ProductCategoryController
 * @package App\Http\Controllers\Api\Platform
 */
class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        Request $request
    ) {
        return ProductCategoryResource::collection(
            $request->input('all', false) ?
            ProductCategory::all():
            Implementation::activeProductCategories()
        );
    }
}
