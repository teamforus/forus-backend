<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Products\IndexProductsRequest;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Products\ProductDigestLogsRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\FundProviderProductDigestResource;
use App\Http\Resources\SponsorProductResource;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundProviderProductQuery;
use App\Scopes\Builders\ProductQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @noinspection PhpUnused
 */
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
        $this->authorize('listAllSponsorProduct', [Product::class, $organization]);

        $query = Product::searchAny($request, null, false);
        $fundProviders = FundProvider::search(
            new BaseFormRequest(), $organization
        )->pluck('organization_id')->toArray();

        $query = ProductQuery::sortByDigestLogs(
            ProductQuery::whereNotExpired((clone $query)->whereIn('organization_id', $fundProviders))
        );

        return SponsorProductResource::queryCollection($query, $request);
    }

    /**
     * @param ProductDigestLogsRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function getDigestLogs(
        ProductDigestLogsRequest $request,
        Organization $organization
    ): AnonymousResourceCollection {
        $this->authorize('listAllSponsorProduct', [Product::class, $organization]);

        $productQuery = Product::searchAny($request, null, false);
        $query = FundProviderProductQuery::whereHasSponsorDigestLogs(
            $productQuery,
            $organization,
            $request->get('product_id'),
            $request->get('fund_id'),
        );

        if ($request->get('group_by') == 'per_product') {
            $query->groupBy('product_id');
        }

        return FundProviderProductDigestResource::queryCollection($query, $request);
    }
}
