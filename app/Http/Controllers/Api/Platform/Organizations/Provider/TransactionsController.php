<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Provider;

use App\Http\Requests\Api\Platform\Organizations\Transactions\IndexTransactionsRequest;
use App\Http\Resources\Provider\ProviderVoucherTransactionResource;
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
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexTransactionsRequest $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('indexProvider', [VoucherTransaction::class, $organization]);

        return ProviderVoucherTransactionResource::collection(
            VoucherTransaction::searchProvider($organization, $request)->with(
                ProviderVoucherTransactionResource::$load
            )->paginate(
                $request->input('per_page', 25)
            )
        );
    }

    /**
     * @param Organization $organization
     * @param VoucherTransaction $voucherTransaction
     * @return ProviderVoucherTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        VoucherTransaction $voucherTransaction
    ) {
        $this->authorize('show', $organization);
        $this->authorize('showProvider', [$voucherTransaction, $organization]);

        return new ProviderVoucherTransactionResource($voucherTransaction->load(
            ProviderVoucherTransactionResource::$load
        ));
    }
}
