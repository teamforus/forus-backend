<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Funds\IndexFundsRequest;
use App\Http\Resources\ProductFundResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundsRequest $request
     * @param Organization $organization
     * @param Product $product
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundsRequest $request,
        Organization $organization,
        Product $product
    ): AnonymousResourceCollection {
        $this->authorize('show', [$organization]);
        $this->authorize('showFunds', [$product, $organization]);

        /** @var Fund[] $data */
        $query = FundQuery::whereHasProviderFilter(Fund::search($request->only([
            'tag', 'organization_id', 'fund_id', 'q', 'implementation_id', 'order_by', 'order_by_dir'
        ]), Fund::query()), $organization->id);

        if ($product->sponsor_organization) {
            $query->where([
                'organization_id' => $product->sponsor_organization->id,
                'type' => Fund::TYPE_SUBSIDIES,
            ]);
        }

        $data = $query->paginate(10);

        foreach($data as $dataItem) {
            $dataItem['approved'] = FundQuery::whereProductsAreApprovedFilter(
                Fund::query()->whereId($dataItem->id),
                $product
            )->exists();
        }

        return ProductFundResource::collection($data);
    }
}
