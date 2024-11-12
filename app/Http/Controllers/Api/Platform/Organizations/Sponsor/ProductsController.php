<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Products\IndexProductsRequest;
use App\Http\Resources\Sponsor\SponsorProductResource;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductQuery;
use App\Searches\ProductSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexProductsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(
        IndexProductsRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('listAllSponsorProducts', [Product::class, $organization]);

        $fundsQuery = FundQuery::whereIsConfiguredByForus($organization->funds())->pluck('id');
        $builder = ProductQuery::approvedForFundsFilter(Product::query(), $fundsQuery->toArray());

        $search = new ProductSearch($request->only([
            'q', 'to', 'from', 'updated_to', 'updated_from', 'price_min', 'price_max',
            'has_reservations', 'fund_id', 'order_by', 'order_dir',
        ]), $builder);

        $query = $search->query();

        return SponsorProductResource::queryCollection($query, $request, [
            'sponsor_organization' => $organization,
        ]);
    }

    /**
     * @param Organization $organization
     * @param Product $product
     * @return SponsorProductResource
     */
    public function show(
        Organization $organization,
        Product $product,
    ) {
        $this->authorize('listAllSponsorProducts', [Product::class, $organization]);
        $this->authorize('viewSponsorProduct', [$product, $organization]);

        return SponsorProductResource::create($product, [
            'sponsor_organization' => $organization,
            'with_monitored_history' => true,
        ]);
    }
}
