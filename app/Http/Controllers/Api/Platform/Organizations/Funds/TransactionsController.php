<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Funds;

use App\Http\Requests\Api\Platform\Organizations\Transactions\IndexTransactionsRequest;
use App\Http\Resources\VoucherTransactionResource;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexTransactionsRequest $request
     * @param Organization $organization
     * @param Fund $fund
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(
        IndexTransactionsRequest $request,
        Organization $organization,
        Fund $fund
    ): AnonymousResourceCollection {
        $this->authorize('viewAnyPublic', [VoucherTransaction::class, $fund, $organization]);

        return VoucherTransactionResource::queryCollection($fund->voucher_transactions(), $request);
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
    ): VoucherTransactionResource {
        $this->authorize('showPublic', [$voucherTransaction, $fund, $organization]);

        return VoucherTransactionResource::create($voucherTransaction);
    }
}
