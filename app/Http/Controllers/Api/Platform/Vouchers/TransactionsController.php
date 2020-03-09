<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Http\Requests\Api\Platform\Organizations\Transactions\IndexTransactionsRequest;
use App\Http\Requests\Api\Platform\Vouchers\Transactions\StoreVoucherTransactionRequest;
use App\Http\Resources\VoucherTransactionResource;
use App\Models\Voucher;
use App\Models\VoucherToken;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexTransactionsRequest $request
     * @param VoucherToken $voucherToken
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(
        IndexTransactionsRequest $request,
        VoucherToken $voucherToken
    ) {
        $this->authorize('show', $voucherToken->voucher);
        $this->authorize('viewAny', [VoucherTransaction::class, $voucherToken]);

        return VoucherTransactionResource::collection(
            VoucherTransaction::searchVoucher($voucherToken->voucher, $request)->paginate(
                $request->input('per_page', 25)
            )
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreVoucherTransactionRequest $request
     * @param VoucherToken $voucherToken
     * @return VoucherTransactionResource|\Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(
        StoreVoucherTransactionRequest $request,
        VoucherToken $voucherToken
    ) {
        $this->authorize('useAsProvider', $voucherToken->voucher);

        $product = false;
        $needsReview = false;
        $voucher = $voucherToken->voucher;
        $note = $request->input('note', false);

        if ($voucher->type == Voucher::TYPE_PRODUCT) {
            $amount = $voucher->amount;
            $product = $voucher->product;
            $organizationId = $product->organization_id;

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
        } else {
            $organizationId = $request->input('organization_id');
            $amount = $request->input('amount');

            if ($amount > $voucher->amount_available) {
                return response()->json([
                    'message' => trans('validation.voucher.not_enough_funds'),
                    'key' => 'not_enough_funds'
                ], 403);
            }
        }

        // TODO: cleanup
        if ($threshold = env('VOUCHER_TRANSACTION_REVIEW_THRESHOLD', 5)) {
            $needsReview = $voucher->transactions()->where(
                'created_at', '>=', now()->subSeconds($threshold)
            )->exists();
        }

        /** @var VoucherTransaction $transaction */
        $transaction = $voucher->transactions()->create(array_merge([
            'amount' => $amount,
            'product_id' => $product ? $product->id : null,
            'address' => token_generator()->address(),
            'organization_id' => $organizationId,
        ], $needsReview ? [
            'attempts' => 50,
            'last_attempt_at' => now()
        ] : []));

        if ($note) {
            $transaction->addNote('provider', $note);
        }

        VoucherTransactionCreated::dispatch($transaction);

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
