<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Events\Products\ProductCreated;
use App\Events\Products\ProductUpdated;
use App\Http\Requests\Api\Platform\Organizations\Products\IndexProductRequest;
use App\Http\Requests\Api\Platform\Organizations\Products\StoreProductRequest;
use App\Http\Requests\Api\Platform\Organizations\Products\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Notifications\Organizations\Products\ProductActionsRemovedNotification;
use App\Scopes\Builders\FundQuery;
use App\Services\MediaService\Models\Media;

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
    ) {
        $this->authorize('viewAnyPublic', [Product::class, $organization]);

        return ProductResource::collection(Product::searchAny($request)->where([
            'organization_id' => $organization->id
        ])->paginate($request->input('per_page', 15)));
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

        $media = null;
        $unlimited_stock = $request->input('unlimited_stock', false);
        $total_amount = $request->input('total_amount');

        if ($media_uid = $request->input('media_uid')) {
            $media = $this->mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        /** @var Product $product */
        $product = $organization->products()->create(array_merge($request->only([
            'name', 'description', 'price', 'old_price', 'product_category_id',
            'expire_at'
        ]), [
            'total_amount' => $unlimited_stock ? 0 : $total_amount,
            'unlimited_stock' => $unlimited_stock
        ]));

        ProductCreated::dispatch($product);

        if ($media instanceof Media && $media->type == 'product_photo') {
            $product->attachMedia($media);
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

        $media = null;
        $unlimited_stock = $product->unlimited_stock;
        $total_amount = $request->input('total_amount');

        if ($media_uid = $request->input('media_uid')) {
            $media = $this->mediaService->findByUid($media_uid);

            $this->authorize('destroy', $media);
        }

        if (round($request->input('price'), 2) !== round($product->price, 2)) {
            $subsidiesFunds = FundQuery::whereProductsAreApprovedAndActiveFilter(
                Fund::query(), $product->id
            )->get()->filter(function (Fund $fund) {
                return $fund->isTypeSubsidy();
            });

            if ($subsidiesFunds->count()) {
                foreach ($subsidiesFunds as $fund) {
                    /** @var Fund $fund */
                    logger()->info('organization: '. print_r($fund->organization->id, true));
                    ProductActionsRemovedNotification::send(
                        $product->log(Product::EVENT_ACTIONS_REMOVED, [
                            'product'  => $product,
                            'fund'     => $fund,
                            'sponsor'  => $fund->organization,
                            'provider' => $product->organization
                        ])
                    );
                }

                $product->fund_provider_products()->whereNotNull(
                    'limit_total'
                )->delete();
            }
        }

        $product->update(array_merge($request->only([
            'name', 'description', 'price', 'old_price', 'sold_amount',
            'product_category_id', 'expire_at'
        ]), [
            'total_amount' => $unlimited_stock ? 0 : $total_amount
        ]));

        if ($media instanceof Media && $media->type == 'product_photo') {
            $product->attachMedia($media);
        }

        ProductUpdated::dispatch($product);

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
