<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Provider;

use App\Http\Resources\Provider\ProviderVoucherTransactionResource;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        Request $request,
        Organization $organization
    ) {
        $this->authorize('show', $organization);
        $this->authorize('indexProvider', [VoucherTransaction::class, $organization]);

        return ProviderVoucherTransactionResource::collection(
            $organization->voucher_transactions()->paginate(
                min($request->input('per_page', 25), 100)
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

        return new ProviderVoucherTransactionResource($voucherTransaction);
    }
}
