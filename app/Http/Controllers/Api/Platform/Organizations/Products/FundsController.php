<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Funds\IndexFundsRequest;
use App\Http\Resources\ProductFundResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundQuery;

class FundsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundsRequest $request
     * @param Organization $organization
     * @param Product $product
     * @return string
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundsRequest $request,
        Organization $organization,
        Product $product
    ) {
        $this->authorize('show', [$organization]);
        $this->authorize('showFunds', [$product, $organization]);

        /** @var Fund[] $data */
        $data = FundQuery::whereHasProviderFilter(
            Fund::search($request),
            $organization->id
        )->paginate(10);

        foreach($data as $dataItem) {
            $dataItem['approved'] = FundQuery::whereProductsAreApprovedFilter(
                Fund::query()->whereId($dataItem->id),
                $product->id
            )->exists();
        }

        return ProductFundResource::collection($data);
    }
}
