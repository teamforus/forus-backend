<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Sponsor\Products\ProductDigestLogsRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\SponsorProductEventLogs;
use App\Http\Resources\SponsorProductResource;
use App\Models\FundProvider;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\ProductQuery;
use App\Services\EventLogService\Models\EventLog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @noinspection PhpUnused
 */
class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function index(
        BaseFormRequest $request,
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
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Product $product
     * @return SponsorProductResource
     */
    public function show(
        Organization $organization,
        Product $product,
    ): SponsorProductResource {
        $this->authorize('show', $organization);
        $this->authorize('showAllSponsorProduct', [$product, $organization]);

        return SponsorProductResource::create($product);
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
        $fundProviders = FundProvider::search(
            new BaseFormRequest(), $organization
        )->pluck('organization_id')->toArray();

        $productQuery = ProductQuery::whereNotExpired(
            (clone $productQuery)->whereIn('organization_id', $fundProviders)
        );
        $productIds = $request->has('product_id') ?
            [$request->get('product_id')] : $productQuery->pluck('id')->toArray();

        $query = EventLog::whereIn('loggable_id', $productIds)->where([
            'loggable_type' => 'product',
            'event' => Product::EVENT_UPDATED_DIGEST
        ])->orderBy('created_at');

        if ($request->get('group_by') == 'per_product') {
            $query->groupBy('loggable_id');
        }

        return SponsorProductEventLogs::queryCollection($query, $request);
    }
}
