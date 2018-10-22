<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Provider;

use App\Http\Resources\Provider\ProviderVoucherTransactionResource;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        Organization $organization
    ) {
        return ProviderVoucherTransactionResource::collection(
            $organization->voucher_transactions
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
        $this->authorize('update', $organization);
        $this->authorize('showProvider', $voucherTransaction);

        return new ProviderVoucherTransactionResource($voucherTransaction);
    }
}
