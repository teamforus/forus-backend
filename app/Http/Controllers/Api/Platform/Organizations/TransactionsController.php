<?php

namespace App\Http\Controllers\Api\Platform\Organizations;

use App\Http\Resources\VoucherTransactionResource;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        Request $request,
        Organization $organization
    ) {
        return VoucherTransactionResource::collection(
            VoucherTransaction::query()->whereIn(
                'id', $organization->voucher_transactions()->pluck('id')
            )->orWhereIn(
                'voucher_id', $organization->vouchers()->pluck('vouchers.id')
            )->get()
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Organization $organization
     * @param VoucherTransaction $voucherTransaction
     * @return VoucherTransactionResource
     */
    public function show(
        Request $request,
        Organization $organization,
        VoucherTransaction $voucherTransaction
    ) {
        return new VoucherTransactionResource(VoucherTransaction::query()->whereIn(
            'id', $organization->voucher_transactions()->pluck('id')
        )->orWhereIn(
            'voucher_id', $organization->vouchers()->pluck('vouchers.id')
        )->where('id', $voucherTransaction->id)->get()->first());
    }
}
