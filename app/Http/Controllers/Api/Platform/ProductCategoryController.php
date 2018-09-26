<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use App\Http\Controllers\Controller;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        return auth()->user();

        return ProductCategoryResource::collection(ProductCategory::all());
    }
}
