<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\Products\StoreProductRequest;
use App\Http\Requests\Api\Platform\Organizations\Products\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Organization;
use App\Models\Product;
use App\Http\Controllers\Controller;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('index', Product::class);

        return ProductResource::collection($organization->products);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductRequest $request
     * @param Organization $organization
     * @return ProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreProductRequest $request,
        Organization $organization
    ) {
        $this->authorize('update', $organization);
        $this->authorize('store', [
            Product::class, $organization
        ]);

        return new ProductResource($organization->products()->create(
            $request->only([
                'name', 'description', 'price', 'old_price', 'total_amount',
                'sold_amount', 'product_category_id'
            ])
        ));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Product $product
     * @return ProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Product $product
    ) {
        $this->authorize('show', $organization);
        $this->authorize('show', $product);

        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProductRequest $request
     * @param Organization $organization
     * @param Product $product
     * @return ProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateProductRequest $request,
        Organization $organization,
        Product $product
    ) {
        $this->authorize('update', $organization);
        $this->authorize('update', $product);

        $product->update($request->only([
            'name', 'description', 'price', 'old_price', 'total_amount',
            'sold_amount', 'product_category_id'
        ]));

        return new ProductResource($product);
    }
}
