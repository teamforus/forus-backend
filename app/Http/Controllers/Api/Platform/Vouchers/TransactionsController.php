<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Vouchers\Transactions\IndexVoucherTransactionsRequest;
use App\Http\Requests\Api\Platform\Vouchers\Transactions\StoreVoucherTransactionRequest;
use App\Http\Resources\VoucherTransactionResource;
use App\Models\Organization;
use App\Models\Product;
use App\Models\VoucherToken;
use App\Models\VoucherTransaction;
use App\Searches\VoucherTransactionsSearch;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexVoucherTransactionsRequest $request
     * @param VoucherToken $voucherToken
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(
        IndexVoucherTransactionsRequest $request,
        VoucherToken $voucherToken
    ): AnonymousResourceCollection {
        $this->authorize('show', $voucherToken->voucher);
        $this->authorize('viewAny', [VoucherTransaction::class, $voucherToken]);

        $builder = new VoucherTransactionsSearch($request->only([
            'q', 'targets', 'state', 'from', 'to', 'amount_min', 'amount_max',
            'transfer_in_min', 'transfer_in_max', 'fund_state',
        ]), $voucherToken->voucher->all_transactions());

        $query = $builder->query();

        if ($request->isMeApp()) {
            $query->where('target', VoucherTransaction::TARGET_PROVIDER);
        }

        return VoucherTransactionResource::queryCollection($query, $request);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreVoucherTransactionRequest $request
     * @param VoucherToken $voucherToken
     * @throws AuthorizationException|Throwable
     * @return VoucherTransactionResource
     */
    public function store(
        StoreVoucherTransactionRequest $request,
        VoucherToken $voucherToken
    ): VoucherTransactionResource {
        if ($voucherToken->voucher->isBudgetType() && $request->has('product_id')) {
            $this->authorize('useAsProviderWithProducts', [
                $voucherToken->voucher,
                $request->input('product_id'),
            ]);
        } else {
            $this->authorize('useAsProvider', $voucherToken->voucher);
        }

        $this->authorize('makeTransactionThrottle', $voucherToken->voucher);

        $note = $request->input('note', false);
        $voucher = $voucherToken->voucher;
        $product = null;
        $transactionState = VoucherTransaction::STATE_PENDING;
        $amountExtraCash = null;

        // Product reservation voucher
        if ($voucher->product_reservation) {
            $this->authorize('acceptProvider', [
                $voucher->product_reservation,
                $voucher->product_reservation->product->organization,
            ]);

            return new VoucherTransactionResource(
                $voucher->product_reservation->acceptByApp($request->auth_address(), $note)
            );
        }

        if ($voucher->isBudgetType()) {
            if ($request->has('product_id')) {
                $product = Product::findOrFail($request->input('product_id'));
                $amount = $product->price;
                $organization = $product->organization;
                $fundProviderProduct = $product?->getFundProviderProduct($voucher->fund);

                if ($fundProviderProduct->isPaymentTypeSubsidy()) {
                    $amount = $fundProviderProduct->amount;

                    if ($amount === 0) {
                        $transactionState = VoucherTransaction::STATE_SUCCESS;
                    }
                }
            } else {
                // budget fund and budget voucher
                $amount = $request->input('amount');
                $organization = Organization::findOrFail($request->input('organization_id'));
            }

            $amountExtraCash = $request->input('amount_extra_cash');
        } else {
            // budget fund and product voucher
            $amount = $voucher->amount;
            $product = $voucher->product;
            $organization = $product->organization;
            $fundProviderProduct = $product?->getFundProviderProduct($voucher->fund);
        }

        $employee = $organization->findEmployee($request->auth_address());

        $transaction = $voucher->makeTransaction([
            'amount' => $amount,
            'product_id' => $product->id ?? null,
            'employee_id' => $employee?->id,
            'branch_id' => $employee?->office?->branch_id,
            'branch_number' => $employee?->office?->branch_number,
            'branch_name' => $employee?->office?->branch_name,
            'state' => $transactionState,
            'fund_provider_product_id' => $fundProviderProduct?->id ?? null,
            'target' => VoucherTransaction::TARGET_PROVIDER,
            'organization_id' => $organization->id,
            'amount_extra_cash' => $amountExtraCash,
        ], $voucher->needsTransactionReview());

        $note && $transaction->addNote('provider', $note);

        VoucherTransactionCreated::dispatch($transaction, $note ? [
            'voucher_transaction_note' => $note,
        ] : []);

        return VoucherTransactionResource::create($transaction);
    }

    /**
     * Display the specified resource.
     *
     * @param VoucherToken $voucherToken
     * @param VoucherTransaction $voucherTransaction
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @return VoucherTransactionResource
     */
    public function show(
        VoucherToken $voucherToken,
        VoucherTransaction $voucherTransaction
    ): VoucherTransactionResource {
        $this->authorize('show', [$voucherTransaction, $voucherToken]);

        if ($voucherTransaction->voucher_id !== $voucherToken->voucher_id) {
            abort(404);
        }

        return VoucherTransactionResource::create($voucherTransaction);
    }
}
