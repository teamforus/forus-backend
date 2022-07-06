<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Http\Controllers\Controller;
use App\Http\Resources\TopUpTransactionResource;
use App\Models\Fund;
use App\Models\FundTopUp;
use App\Models\FundTopUpTransaction;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FundTopUpTransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @param Fund $fund
     * @return AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request,
        Organization $organization,
        Fund $fund
    ): AnonymousResourceCollection {
        $this->authorize('show', $organization);
        $this->authorize('show', [$fund, $organization]);

        return TopUpTransactionResource::collection(
            FundTopUpTransaction::search($request, $fund)->paginate($request->input('per_page', 10))
        );
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
        $this->authorize('show', [$fund, $organization]);

        return new TopUpTransactionResource($fundTopUp);
    }
}
