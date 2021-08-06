<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds\FundProviders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\Products\IndexFundProviderProductsRequest;
use App\Scopes\Builders\ProductQuery;
use App\Http\Resources\Sponsor\SponsorProviderProductResource;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Product;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class ProductsController
 * @package App\Http\Controllers\Api\Platform\Organizations\Funds\FundProviders
 */
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

        return SponsorProviderProductResource::collection($query->with(
            SponsorProviderProductResource::$load
        )->paginate($request->input('per_page')));
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundProvider $fundProvider
     * @param Product $product
     * @return SponsorProviderProductResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundProvider $fundProvider,
        Product $product
    ): SponsorProviderProductResource {
        $this->authorize('showSponsor', [$fundProvider, $organization, $fund]);

        return new SponsorProviderProductResource(
            $product->load(SponsorProviderProductResource::$load)
        );
    }
}
