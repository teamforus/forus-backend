<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Http\Requests\Api\Platform\Vouchers\Transactions\StoreVoucherTransactionRequest;
use App\Http\Resources\VoucherTransactionResource;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Voucher $voucher
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        Voucher $voucher
    ) {
        return VoucherTransactionResource::collection($voucher->transactions);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreVoucherTransactionRequest $request
     * @param Voucher $voucher
     * @return VoucherTransactionResource
     */
    public function store(
        StoreVoucherTransactionRequest $request,
        Voucher $voucher
    ) {
        /**
         * @var Voucher $voucher
         * @var Product|null $product
         */
        $maxAmount = $voucher->amount - $voucher->transactions->sum('amount');
        $product = Product::getModel()->find($request->get('product_id'));

        if (!$product) {
            $amount = $request->input('amount');
        } else {
            $amount = $product->price;
        }

        if ($amount > $maxAmount) {
            return response()->json([
                'message' => trans('validation.voucher.not_enough_funds'),
                'key' => 'not_enough_funds'
            ], 403);
        }

        $transaction = $voucher->transactions()->create([
            'amount' => $amount,
            'product_id' => $product ? $product->id : null,
            'address' => app()->make('token_generator')->address(),
            'organization_id' => $request->input('organization_id'),
        ]);

        return new VoucherTransactionResource($transaction);
    }

    /**
     * Display the specified resource.
     *
     * @param Voucher $voucher
     * @param VoucherTransaction $voucherTransaction
     * @return VoucherTransactionResource
     */
    public function show(
        Voucher $voucher,
        VoucherTransaction $voucherTransaction
    ) {
        if ($voucherTransaction->voucher_id != $voucher->id) {
            abort(404);
        }

        return new VoucherTransactionResource($voucherTransaction);
    }
}
