<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Http\Requests\Api\Platform\Vouchers\Transactions\StoreVoucherTransactionRequest;
use App\Http\Resources\VoucherTransactionResource;
use App\Models\Product;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param VoucherToken $voucherToken
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        VoucherToken $voucherToken
    ) {
        $this->authorize('show', $voucherToken->voucher);
        $this->authorize('index', [VoucherTransaction::class, $voucherToken]);

        return VoucherTransactionResource::collection(
            $voucherToken->voucher->transactions
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreVoucherTransactionRequest $request
     * @param VoucherToken $voucherToken
     * @return VoucherTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreVoucherTransactionRequest $request,
        VoucherToken $voucherToken
    ) {
        $voucher = $voucherToken->voucher;

        $this->authorize('useAsProvider', $voucher);

        /** @var Product|null $product */
        if ($voucher->type == 'product') {
            $product = $voucher->product;
            $amount = $voucher->amount;
            $organizationId = $product->organization_id;
        } else {
            $maxAmount = $voucher->amount - $voucher->transactions->sum('amount');
            $product = Product::getModel()->find($request->get('product_id'));

            if (!$product) {
                $amount = $request->input('amount');
                $organizationId = $request->input('organization_id');
            } else {
                $amount = $product->price;
                $organizationId = $product->organization_id;
            }

            if ($amount > $maxAmount) {
                return response()->json([
                    'message' => trans('validation.voucher.not_enough_funds'),
                    'key' => 'not_enough_funds'
                ], 403);
            }
        }

        if ($product) {
            if ($product->expired) {
                return response()->json([
                    'message' => trans('validation.voucher.product_expired'),
                    'key' => 'product_expired'
                ], 403);
            }

            if ($product->sold_out && $voucher->type != 'product') {
                return response()->json([
                    'message' => trans('validation.voucher.product_sold_out'),
                    'key' => 'product_expired'
                ], 403);
            }
        }

        /** @var VoucherTransaction $transaction */
        $transaction = $voucher->transactions()->create([
            'amount' => $amount,
            'product_id' => $product ? $product->id : null,
            'address' => app()->make('token_generator')->address(),
            'organization_id' => $organizationId,
        ]);

        if ($product) {
            $product->updateSoldOutState();
        }

        $note = $request->input('note', false);

        if ($note && !empty($note)) {
            $transaction->notes()->create([
                'message' => $note,
                'group' => 'provider'
            ]);
        }

        return new VoucherTransactionResource($transaction);
    }

    /**
     * Display the specified resource.
     *
     * @param VoucherToken $voucherToken
     * @param VoucherTransaction $voucherTransaction
     * @return VoucherTransactionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(
        VoucherToken $voucherToken,
        VoucherTransaction $voucherTransaction
    ) {
        $this->authorize('show', [$voucherTransaction, $voucherToken]);

        $voucher = $voucherToken->voucher;

        if ($voucherTransaction->voucher_id != $voucher->id) {
            abort(404);
        }

        return new VoucherTransactionResource($voucherTransaction);
    }
}
