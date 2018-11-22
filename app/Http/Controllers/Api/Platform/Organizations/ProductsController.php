<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Requests\Api\Platform\Organizations\Products\StoreProductRequest;
use App\Http\Requests\Api\Platform\Organizations\Products\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Fund;
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
        $this->authorize('index', [Product::class, $organization]);

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
        $this->authorize('show', $organization);
        $this->authorize('store', [Product::class, $organization]);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $mediaService = app()->make('media');
            $media = $mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        /** @var Product $product */
        $product = $organization->products()->create(
            $request->only([
                'name', 'description', 'price', 'old_price', 'total_amount',
                'product_category_id', 'expire_at'
            ])
        );

        if ($media && $media->type == 'product_photo') {
            $product->attachMedia($media);
        }

        $notifiedIdentities = [];

        /** @var Fund $fund */
        foreach ($organization->supplied_funds_approved as $fund) {
            $productCategories = $fund->product_categories()->pluck(
                'product_categories.id'
            );

            if ($productCategories->search(
                $product->product_category_id) !== false
            ) {
                if (in_array(
                    $fund->organization->identity_address,
                    $notifiedIdentities
                )) {
                    continue;
                }

                array_push(
                    $notifiedIdentities,
                    $fund->organization->identity_address
                );

                resolve('forus.services.mail_notification')->newProductAdded(
                    $product->organization->identity_address,
                    $product->organization->name,
                    $fund->name
                );
            }
        }

        return new ProductResource($product);
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
        $this->authorize('show', [$product, $organization]);

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
        $this->authorize('show', $organization);
        $this->authorize('update', [$product, $organization]);

        $media = false;

        if ($media_uid = $request->input('media_uid')) {
            $mediaService = app()->make('media');
            $media = $mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        $product->update($request->only([
            'name', 'description', 'price', 'old_price', 'total_amount',
            'sold_amount', 'product_category_id', 'expire_at'
        ]));

        if ($media && $media->type == 'product_photo') {
            $product->attachMedia($media);
        }

        return new ProductResource($product);
    }

    /**
     * Destroy the specified resource in storage.
     *
     * @param Organization $organization
     * @param Product $product
     * @return string
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(
        Organization $organization,
        Product $product
    ) {
        $this->authorize('show', $organization);
        $this->authorize('destroy', [$product, $organization]);

        $product->delete();

        return "";
    }
}
