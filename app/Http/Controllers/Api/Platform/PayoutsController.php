<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Payouts\IndexPayoutsRequest;
use App\Http\Resources\VoucherTransactionPayoutResource;
use App\Models\VoucherTransaction;
use App\Searches\VoucherTransactionsSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PayoutsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexPayoutsRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(IndexPayoutsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [VoucherTransaction::class]);

        $query = VoucherTransaction::query()->where(function(Builder $builder) use ($request) {
            $builder->whereRelation(
                'payout_relations', 'value', $request->identity()->email
            )->orWhereRelation(
                'payout_relations', 'value', $request->identity()->bsn
            );
        })->where('target', VoucherTransaction::TARGET_PAYOUT);

        $search = new VoucherTransactionsSearch($request->only(['q']), $query);

        return VoucherTransactionPayoutResource::queryCollection($search->query());
    }
}
