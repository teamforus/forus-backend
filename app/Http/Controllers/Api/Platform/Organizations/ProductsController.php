<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\Products\ProductCreated;
use App\Events\Products\ProductUpdated;
use App\Http\Requests\Api\Platform\Organizations\Products\IndexProductRequest;
use App\Http\Requests\Api\Platform\Organizations\Products\StoreProductRequest;
use App\Http\Requests\Api\Platform\Organizations\Products\UpdateProductExclusionsRequest;
use App\Http\Requests\Api\Platform\Organizations\Products\UpdateProductRequest;
use App\Http\Resources\Provider\ProviderProductResource;
use App\Models\Organization;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ProductsController
 * @package App\Http\Controllers\Api\Platform\Organizations
 */
class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductRequest $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexProductRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyPublic', [Product::class, $organization]);

        return ProviderProductResource::collection(Product::searchAny($request)->where([
            'organization_id' => $organization->id
        ])->paginate($request->input('per_page', 15)))->additional([
            'meta' => $organization->productsMeta()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductRequest $request
     * @param Organization $organization
     * @return ProviderProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreProductRequest $request,
        Organization $organization
    ): ProviderProductResource {
        $this->authorize('show', $organization);
        $this->authorize('store', [Product::class, $organization]);

        $product = Product::storeFromRequest($organization, $request);
        ProductCreated::dispatch($product);

        return new ProviderProductResource($product);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Product $product
     * @return ProviderProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Organization $organization, Product $product): ProviderProductResource
    {
        $this->authorize('show', $organization);
        $this->authorize('show', [$product, $organization]);

        return new ProviderProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProductRequest $request
     * @param Organization $organization
     * @param Product $product
     * @return ProviderProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateProductRequest $request,
        Organization $organization,
        Product $product
    ): ProviderProductResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$product, $organization]);

        $product->updateFromRequest($request);
        ProductUpdated::dispatch($product);

        return new ProviderProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProductExclusionsRequest $request
     * @param Organization $organization
     * @param Product $product
     * @return ProviderProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateExclusions(
        UpdateProductExclusionsRequest $request,
        Organization $organization,
        Product $product
    ): ProviderProductResource {
        $this->authorize('show', $organization);
        $this->authorize('update', [$product, $organization]);

        $product->updateExclusions($request);

        ProductUpdated::dispatch($product);
        return new ProviderProductResource($product);
    }

    /**
     * Destroy the specified resource in storage.
     *
     * @param Organization $organization
     * @param Product $product
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function destroy(Organization $organization, Product $product): JsonResponse
    {
        $this->authorize('show', $organization);
        $this->authorize('destroy', [$product, $organization]);

        $product->delete();

        return response()->json([]);
    }
}
