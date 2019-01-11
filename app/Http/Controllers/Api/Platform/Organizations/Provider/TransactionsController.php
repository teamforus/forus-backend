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
     * @param Organization $organization
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        Organization $organization,
        Request $request
    ) {
        $this->authorize('show', $organization);
        $this->authorize('indexProvider', [VoucherTransaction::class, $organization]);

        return ProviderVoucherTransactionResource::collection(
            $organization->voucher_transactions()->paginate(
                $request->has('per_page') ? $request->input('per_page') : null
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
