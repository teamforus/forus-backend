<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor\Providers;

use App\Events\Products\ProductCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers\Products\IndexProductsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers\Products\StoreProductsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Providers\Products\UpdateProductsRequest;
use App\Http\Resources\Sponsor\SponsorProductResource;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\ProductQuery;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductsRequest $request
     * @param Organization $sponsor
     * @param Organization $provider
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexProductsRequest $request,
        Organization $sponsor,
        Organization $provider
    ): AnonymousResourceCollection {
        $this->authorize('listSponsorProduct', [Product::class, $provider, $sponsor]);

        $query = Product::searchAny($request, $provider->products()->getQuery());
        $query = ProductQuery::whereNotExpired($query->where([
            'sponsor_organization_id' => $sponsor->id,
        ]))->latest();

        return SponsorProductResource::queryCollection($query, $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreProductsRequest $request
     * @param Organization $sponsor
     * @param Organization $provider
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return SponsorProductResource
     */
    public function store(
        StoreProductsRequest $request,
        Organization $sponsor,
        Organization $provider
    ): SponsorProductResource {
        $this->authorize('storeSponsorProduct', [Product::class, $provider, $sponsor]);

        $product = Product::storeFromRequest($provider, $request);

        ProductCreated::dispatch($product->updateModel([
            'sponsor_organization_id' => $sponsor->id,
        ]));

        return SponsorProductResource::create($product)->additional([
            'fund_provider' => null,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $sponsor
     * @param Organization $provider
     * @param Product $product
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return SponsorProductResource
     */
    public function show(
        Organization $sponsor,
        Organization $provider,
        Product $product
    ): SponsorProductResource {
        $this->authorize('show', $sponsor);
        $this->authorize('viewAnySponsor', [FundProvider::class, $sponsor]);
        $this->authorize('showSponsorProduct', [$product, $provider, $sponsor]);

        return SponsorProductResource::create($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateProductsRequest $request
     * @param Organization $sponsor
     * @param Organization $provider
     * @param \App\Models\Product $product
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return SponsorProductResource
     */
    public function update(
        UpdateProductsRequest $request,
        Organization $sponsor,
        Organization $provider,
        Product $product
    ): SponsorProductResource {
        $this->authorize('show', $sponsor);
        $this->authorize('viewAnySponsor', [FundProvider::class, $sponsor]);
        $this->authorize('updateSponsorProduct', [$product, $provider, $sponsor]);

        $product->updateFromRequest($request, true);

        return SponsorProductResource::create($product);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Organization $sponsor
     * @param Organization $provider
     * @param \App\Models\Product $product
     * @throws Exception
     * @return JsonResponse
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
