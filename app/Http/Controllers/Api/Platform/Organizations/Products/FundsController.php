<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Funds\IndexFundsRequest;
use App\Http\Resources\ProductFundResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use App\Searches\FundSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundsRequest $request
     * @param Organization $organization
     * @param Product $product
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexFundsRequest $request,
        Organization $organization,
        Product $product
    ): AnonymousResourceCollection {
        $this->authorize('show', [$organization]);
        $this->authorize('showFunds', [$product, $organization]);

        /** @var Fund[] $data */
        $query = FundQuery::whereHasProviderFilter((new FundSearch($request->only([
            'tag', 'organization_id', 'fund_id', 'fund_ids', 'q', 'implementation_id',
            'order_by', 'order_dir',
        ]), Fund::query()))->query(), $organization->id);

        if ($product->sponsor_organization) {
            $query->where([
                'organization_id' => $product->sponsor_organization->id,
            ]);
        }

        return ProductFundResource::queryCollection($query, 10, [
            'product' => $product->load('organization.fund_providers'),
        ]);
    }
}
