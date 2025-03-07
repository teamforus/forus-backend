<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Payouts\IndexPayoutsRequest;
use App\Http\Resources\VoucherTransactionPayoutResource;
use App\Models\Voucher;
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

        $query = VoucherTransaction::where(function (Builder $builder) use ($request) {
            $builder->where('target', VoucherTransaction::TARGET_PAYOUT);
            $builder->whereHas('voucher', function (Builder $builder) use ($request) {
                $builder->where('voucher_type', Voucher::VOUCHER_TYPE_PAYOUT);
                $builder->whereRelation('fund_request', 'identity_id', $request->auth_id());
            });
        });

        return VoucherTransactionPayoutResource::queryCollection(
            (new VoucherTransactionsSearch($request->only('q'), $query))->query(),
            $request,
        );
    }
}
