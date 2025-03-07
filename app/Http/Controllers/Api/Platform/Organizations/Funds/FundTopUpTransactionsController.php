<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Funds\IndexFundTopUpTransactionRequest;
use App\Http\Resources\TopUpTransactionResource;
use App\Models\Fund;
use App\Models\FundTopUp;
use App\Models\FundTopUpTransaction;
use App\Models\Organization;
use App\Searches\FundTopUpTransactionSearch;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundTopUpTransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexFundTopUpTransactionRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return AnonymousResourceCollection
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

        $search = new FundTopUpTransactionSearch($request->only([
            'q', 'from', 'to', 'amount_min', 'amount_max', 'order_dir', 'order_by',
        ]), $query);

        return TopUpTransactionResource::queryCollection($search->query());
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param FundTopUp $fundTopUp
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return TopUpTransactionResource
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
