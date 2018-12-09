<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Sponsor;

use App\Http\Resources\Sponsor\SponsorVoucherTransactionResource;
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
        $this->authorize('indexSponsor', [VoucherTransaction::class, $organization]);

        return SponsorVoucherTransactionResource::collection(
            VoucherTransaction::query()->whereIn(
                'voucher_id', $organization->vouchers->pluck('id')
            )->paginate(
                $request->has('per_page') ? $request->input('per_page') : null
            )
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
        $this->authorize('show', $organization);
        $this->authorize('showSponsor', [$voucherTransaction, $organization]);

        return new SponsorVoucherTransactionResource($voucherTransaction);
    }
}
