<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Http\Requests\Api\Platform\Organizations\Transactions\IndexTransactionsRequest;
use App\Http\Resources\VoucherTransactionResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexTransactionsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexTransactionsRequest $request,
        Organization $organization,
        Fund $fund
    ) {
        $this->authorize('indexPublic', [
            VoucherTransaction::class, $fund, $organization
        ]);

        return VoucherTransactionResource::collection(
            $fund->voucher_transactions()->paginate(
                $request->input('per_page', 10)
            )
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param Fund $fund
     * @param VoucherTransaction $voucherTransaction
     * @return VoucherTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        Fund $fund,
        VoucherTransaction $voucherTransaction
    ) {
        $this->authorize('showPublic', [
            $voucherTransaction, $fund, $organization
        ]);

        return new VoucherTransactionResource($voucherTransaction);
    }
}
