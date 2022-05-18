<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Http\Requests\Api\Platform\Organizations\Transactions\IndexTransactionsRequest;
use App\Http\Requests\Api\Platform\Vouchers\Transactions\StoreVoucherTransactionRequest;
use App\Http\Resources\VoucherTransactionResource;
use App\Models\Organization;
use App\Models\Product;
use App\Models\VoucherToken;
use App\Models\VoucherTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
    ): AnonymousResourceCollection {
        $this->authorize('show', $voucherToken->voucher);
        $this->authorize('viewAny', [VoucherTransaction::class, $voucherToken]);

        return VoucherTransactionResource::queryCollection(
            VoucherTransaction::searchVoucher($voucherToken->voucher, $request),
            $request
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
    ): VoucherTransactionResource {
        if ($voucherToken->voucher->fund->isTypeBudget() &&
            $voucherToken->voucher->isBudgetType() &&
            $request->has('product_id')) {
            $this->authorize('useAsProviderWithProducts', [
                $voucherToken->voucher,
                $request->input('product_id'),
            ]);
        } else {
            $this->authorize('useAsProvider', $voucherToken->voucher);
        }

        $note = $request->input('note', false);
        $voucher = $voucherToken->voucher;
        $transactionState = VoucherTransaction::STATE_PENDING;

        // Product reservation voucher
        if ($voucher->product_reservation) {
            $this->authorize('acceptProvider', [
                $voucher->product_reservation,
                $voucher->product_reservation->product->organization
            ]);

            return new VoucherTransactionResource(
                $voucher->product_reservation->acceptByApp($request->auth_address(), $note)
            );
        }

        // Budget fund voucher
        if ($voucher->fund->isTypeBudget()) {
            if ($voucher->isBudgetType()) {
                if ($request->has('product_id')) {
                    $product = Product::findOrFail($request->input('product_id'));
                    $amount = $product->price;
                    $organization = $product->organization;
                } else {
                    // budget fund and budget voucher
                    $amount = $request->input('amount');
                    $organization = Organization::findOrFail($request->input('organization_id'));
                }
            } else {
                // budget fund and product voucher
                $amount = $voucher->amount;
                $product = $voucher->product;
                $organization = $product->organization;
            }
        } else {
            // Subsidy fund voucher
            $product = Product::findOrFail($request->input('product_id'));
            $fundProviderProduct = $product->getSubsidyDetailsForFundOrFail($voucher->fund);

            $fundProviderProductId = $fundProviderProduct->id;
            $organization = $product->organization;
            $amount = $fundProviderProduct->amount;

            if ($amount === 0) {
                $transactionState = VoucherTransaction::STATE_SUCCESS;
            }
        }

        /** @var VoucherTransaction $transaction */
        $transaction = $voucher->transactions()->create(array_merge([
            'amount' => $amount,
            'product_id' => $product->id ?? null,
            'employee_id' => $organization->findEmployee($request->auth_address())->id,
            'state' => $transactionState,
            'fund_provider_product_id' => $fundProviderProductId ?? null,
            'address' => resolve('token_generator')->address(),
            'organization_id' => $organization->id,
        ], $voucher->needsTransactionReview() ? [
            'attempts' => 50,
            'last_attempt_at' => now()
        ] : []));

        $note && $transaction->addNote('provider', $note);

        VoucherTransactionCreated::dispatch($transaction);

        return VoucherTransactionResource::create($transaction);
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
    ): VoucherTransactionResource  {
        $this->authorize('show', [$voucherTransaction, $voucherToken]);

        if ($voucherTransaction->voucher_id !== $voucherToken->voucher_id) {
            abort(404);
        }

        return VoucherTransactionResource::create($voucherTransaction);
    }
}
