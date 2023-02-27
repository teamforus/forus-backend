<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Funds\IndexFundTopUpTransactionRequest;
use App\Http\Resources\TopUpTransactionResource;
use App\Models\Fund;
use App\Models\FundTopUp;
use App\Models\FundTopUpTransaction;
use App\Models\Organization;
use App\Searches\FundTopsUpSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundTopUpTransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundTopUpTransactionRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexFundTopUpTransactionRequest $request,
        Organization $organization,
        Fund $fund
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('showFinances', [$fund, $organization]);

        $query = FundTopUpTransaction::query()
            ->whereRelation('fund_top_up.fund', 'funds.id', $fund->id)
            ->whereNotNull('amount');

        $search = new FundTopsUpSearch($request->only([
            'q', 'from', 'to', 'amount_min', 'amount_max',
        ]), $query);

        return TopUpTransactionResource::queryCollection($search->query());
    }

    /**
     * Display the specified resource
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundTopUp $fundTopUp
     * @return TopUpTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        FundTopUp $fundTopUp
    ): TopUpTransactionResource {
        $this->authorize('show', $organization);
        $this->authorize('showFinances', [$fund, $organization]);

        return TopUpTransactionResource::create($fundTopUp);
    }
}
