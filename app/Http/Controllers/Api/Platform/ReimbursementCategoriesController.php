<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReimbursementCategoryResource;
use App\Models\ReimbursementCategory;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReimbursementCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return ReimbursementCategoryResource::queryCollection(ReimbursementCategory::query());
    }

    /**
     * Display the specified resource.
     *
     * @param ReimbursementCategory $reimbursementCategory
     * @return ReimbursementCategoryResource
     */
    public function show(ReimbursementCategory $reimbursementCategory): ReimbursementCategoryResource
    {
        return ReimbursementCategoryResource::create($reimbursementCategory);
    }
}

