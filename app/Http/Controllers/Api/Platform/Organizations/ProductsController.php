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
use App\Services\MediaService\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductsController extends Controller
{
    private $mediaService;

    /**
     * ProductsController constructor.
     */
    public function __construct()
    {
        $this->mediaService = resolve('media');
    }

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
        ])->paginate($request->input('per_page', 15)));
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

        $media = null;
        $price_type = $request->input('price_type');
        $total_amount = $request->input('total_amount');
        $unlimited_stock = $request->input('unlimited_stock', false);

        $price = in_array($price_type, [
            Product::PRICE_TYPE_REGULAR,
        ]) ? $request->input('price') : 0;

        $price_discount = in_array($price_type, [
            Product::PRICE_TYPE_DISCOUNT_FIXED,
            Product::PRICE_TYPE_DISCOUNT_PERCENTAGE
        ]) ? $request->input('price_discount') : 0;

        if ($media_uid = $request->input('media_uid')) {
            $media = $this->mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        /** @var Product $product */
        $product = $organization->products()->create(array_merge($request->only([
            'name', 'description', 'price', 'product_category_id', 'expire_at',
        ]), [
            'total_amount' => $unlimited_stock ? 0 : $total_amount,
            'unlimited_stock' => $unlimited_stock
        ], compact('price', 'price_type', 'price_discount')));

        ProductCreated::dispatch($product);

        if ($media instanceof Media && $media->type === 'product_photo') {
            $product->attachMedia($media);
        }

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
    public function show(
        Organization $organization,
        Product $product
    ): ProviderProductResource {
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

        $price_type = $request->input('price_type');
        $total_amount = $request->input('total_amount');

        $price = in_array($price_type, [
            $product::PRICE_TYPE_REGULAR,
        ]) ? $request->input('price') : 0;

        $price_discount = in_array($price_type, [
            $product::PRICE_TYPE_DISCOUNT_FIXED,
            $product::PRICE_TYPE_DISCOUNT_PERCENTAGE
        ]) ? $request->input('price_discount') : 0;

        if ($media_uid = $request->input('media_uid')) {
            $media = $this->mediaService->findByUid($media_uid);
            $this->authorize('destroy', $media);

            if ($media instanceof Media && $media->type === 'product_photo') {
                $product->attachMedia($media);
            }
        }

        if ($product->priceWillChanged($price_type, $price, $price_discount)) {
            $product->resetSubsidyApprovals();
        }

        $product->update(array_merge($request->only([
            'name', 'description', 'sold_amount', 'product_category_id', 'expire_at',
        ]), [
            'total_amount' => $product->unlimited_stock ? 0 : $total_amount,
        ], compact('price', 'price_type', 'price_discount')));

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
    public function destroy(
        Organization $organization,
        Product $product
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('destroy', [$product, $organization]);

        $product->delete();

        return response()->json([]);
    }
}
