<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor\Providers;

use App\Events\Products\ProductCreated;
use App\Events\Products\ProductUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers\Products\IndexProductsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers\Products\StoreProductsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers\Products\UpdateProductsRequest;
use App\Http\Resources\Sponsor\SponsorProviderProductResource;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ProductsController
 * @package App\Http\Controllers\Api\Platform\Organizations\Sponsor\Providers
 */
class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductsRequest $request
     * @param Organization $sponsor
     * @param Organization $provider
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexProductsRequest $request,
        Organization $sponsor,
        Organization $provider
    ): AnonymousResourceCollection {
        $this->authorize('listSponsorProduct', [Product::class, $provider, $sponsor]);

        $query = Product::searchAny($request, $provider->products()->getQuery());
        $query = ProductQuery::whereNotExpired($query->where([
            'sponsor_organization_id' => $sponsor->id
        ]))->latest();

        return SponsorProviderProductResource::queryCollection($query, $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductsRequest $request
     * @param Organization $sponsor
     * @param Organization $provider
     * @return SponsorProviderProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreProductsRequest $request,
        Organization $sponsor,
        Organization $provider
    ): SponsorProviderProductResource {
        $this->authorize('storeSponsorProduct', [Product::class, $provider, $sponsor]);

        $product = Product::storeFromRequest($provider, $request);

        ProductCreated::dispatch($product->updateModel([
            'sponsor_organization_id' => $sponsor->id,
        ]));

        return SponsorProviderProductResource::create($product)->additional([
            'fund_provider' => null,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $sponsor
     * @param Organization $provider
     * @param Product $product
     * @return SponsorProviderProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $sponsor,
        Organization $provider,
        Product $product
    ): SponsorProviderProductResource {
        $this->authorize('show', $sponsor);
        $this->authorize('viewAnySponsor', [FundProvider::class, $sponsor]);
        $this->authorize('showSponsorProduct', [$product, $provider, $sponsor]);

        return SponsorProviderProductResource::create($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProductsRequest $request
     * @param Organization $sponsor
     * @param Organization $provider
     * @param \App\Models\Product $product
     * @return SponsorProviderProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(
        UpdateProductsRequest $request,
        Organization $sponsor,
        Organization $provider,
        Product $product
    ): SponsorProviderProductResource {
        $this->authorize('show', $sponsor);
        $this->authorize('viewAnySponsor', [FundProvider::class, $sponsor]);
        $this->authorize('updateSponsorProduct', [$product, $provider, $sponsor]);

        $product->updateFromRequest($request);
        ProductUpdated::dispatch($product);

        return SponsorProviderProductResource::create($product);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $sponsor
     * @param Organization $provider
     * @param \App\Models\Product $product
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(
        Organization $sponsor,
        Organization $provider,
        Product $product
    ): JsonResponse {
        $this->authorize('show', $sponsor);
        $this->authorize('viewAnySponsor', [FundProvider::class, $sponsor]);
        $this->authorize('destroySponsorProduct', [$product, $provider, $sponsor]);

        $product->delete();

        return new JsonResponse([]);
    }
}
