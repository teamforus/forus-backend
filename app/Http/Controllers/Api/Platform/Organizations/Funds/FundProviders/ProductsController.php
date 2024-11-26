<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds\FundProviders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\Products\IndexFundProviderProductsRequest;
use App\Scopes\Builders\ProductQuery;
use App\Http\Resources\Sponsor\SponsorProductResource;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundProviderProductsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundProviderProductsRequest $request,
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider
    ): AnonymousResourceCollection {
        $this->authorize('showSponsor', [$fundProvider, $organization, $fund]);

        $query = $fundProvider->organization->providerProductsQuery($fund->id);

        if ($request->input('q')) {
            $query = ProductQuery::queryFilter($query, $request->input('q'));
        }

        return SponsorProductResource::queryCollection($query, $request);
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param Product $product
     * @return SponsorProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider,
        Product $product
    ): SponsorProductResource {
        $this->authorize('showSponsor', [$fundProvider, $organization, $fund]);

        return SponsorProductResource::create($product);
    }
}
