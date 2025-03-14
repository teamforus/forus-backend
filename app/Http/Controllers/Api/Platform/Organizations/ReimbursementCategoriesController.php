<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Reimbursements\Categories\StoreReimbursementCategoryRequest;
use App\Http\Requests\Api\Platform\Organizations\Reimbursements\Categories\UpdateReimbursementCategoryRequest;
use App\Http\Resources\ReimbursementCategoryResource;
use App\Models\Organization;
use App\Models\ReimbursementCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReimbursementCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('show', $organization);
        $this->authorize('viewAny', [ReimbursementCategory::class, $organization]);

        return ReimbursementCategoryResource::queryCollection($organization->reimbursement_categories());
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param ReimbursementCategory $reimbursementCategory
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ReimbursementCategoryResource
     */
    public function show(
        Organization $organization,
        ReimbursementCategory $reimbursementCategory
    ): ReimbursementCategoryResource {
        $this->authorize('show', $organization);
        $this->authorize('view', [$reimbursementCategory, $organization]);

        return ReimbursementCategoryResource::create($reimbursementCategory);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreReimbursementCategoryRequest $request
     * @param Organization $organization
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ReimbursementCategoryResource
     */
    public function store(
        StoreReimbursementCategoryRequest $request,
        Organization $organization,
    ): ReimbursementCategoryResource {
        $this->authorize('show', $organization);
        $this->authorize('store', [ReimbursementCategory::class, $organization]);

        return ReimbursementCategoryResource::create($organization->reimbursement_categories()->create(
            $request->only('name'),
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateReimbursementCategoryRequest $request
     * @param Organization $organization
     * @param ReimbursementCategory $reimbursementCategory
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return ReimbursementCategoryResource
     */
    public function update(
        UpdateReimbursementCategoryRequest $request,
        Organization $organization,
        ReimbursementCategory $reimbursementCategory,
    ): ReimbursementCategoryResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$reimbursementCategory, $organization]);

        $reimbursementCategory->update($request->only('name'));

        return ReimbursementCategoryResource::create($reimbursementCategory);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $organization
     * @param ReimbursementCategory $reimbursementCategory
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return JsonResponse
     */
    public function destroy(
        Organization $organization,
        ReimbursementCategory $reimbursementCategory,
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('destroy', [$reimbursementCategory, $organization]);

        $reimbursementCategory->delete();

        return new JsonResponse([]);
    }
}
