<?php

namespace App\Services\MeAppApiService\Http\Controllers\Requester;

use App\Http\Controllers\Controller;
use App\Models\Fund;
use App\Scopes\Builders\ImplementationQuery;
use App\Searches\FundSearch;
use App\Services\MeAppApiService\Http\Requests\Requester\IndexFundsRequest;
use App\Services\MeAppApiService\Http\Resources\Requester\FundResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundsController extends Controller
{
    /**
     * List funds for product filtering.
     *
     * @param IndexFundsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(IndexFundsRequest $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'has_providers',
        ]);
        $filters['has_providers'] ??= true;

        $search = new FundSearch($filters, ImplementationQuery::queryFundsByState(Fund::STATE_ACTIVE));

        return FundResource::queryCollection($search->query(), $request)->preserveQuery();
    }
}
