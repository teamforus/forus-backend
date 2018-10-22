<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Resources\Sponsor\SponsorVoucherTransactionResource;
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
        return SponsorVoucherTransactionResource::collection(
            VoucherTransaction::query()->whereIn(
                'id', $organization->vouchers->pluck('id')
            )->get()
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Organization $organization
     * @param VoucherTransaction $voucherTransaction
     * @return SponsorVoucherTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        Organization $organization,
        VoucherTransaction $voucherTransaction
    ) {
        $this->authorize('update', $organization);
        $this->authorize('showSponsor', $voucherTransaction);

        return new SponsorVoucherTransactionResource($voucherTransaction);
    }
}
